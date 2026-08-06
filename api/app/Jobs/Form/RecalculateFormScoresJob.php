<?php

namespace App\Jobs\Form;

use App\Models\Forms\Form;
use App\Service\Forms\FormScoreCalculator;
use App\Service\Forms\ScoreTierResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rescores every submission of a form against its current configuration.
 *
 * `submission.data` is exactly the id => value map the calculator expects, so
 * hidden-block exclusion reproduces the submit-time result -- as long as the
 * form's logic has not changed since. Editing logic or deleting a weighted
 * block will legitimately move historic scores.
 */
class RecalculateFormScoresJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const CACHE_KEY_PREFIX = 'form_score_recalc';

    public $timeout = 900;
    public $tries = 1;

    public function __construct(public Form $form, public string $jobId)
    {
    }

    public static function cacheKey(string $jobId): string
    {
        return self::CACHE_KEY_PREFIX . ':' . $jobId;
    }

    public function handle(): void
    {
        $total = $this->form->submissions()->count();
        $processed = 0;

        $this->updateStatus('processing', 0, $processed, $total);

        $this->form->submissions()->chunkById(500, function ($submissions) use (&$processed, $total) {
            foreach ($submissions as $submission) {
                $result = FormScoreCalculator::compute($this->form, $submission->data ?? []);

                $submission->score = $result['score'];
                $submission->meta = array_merge($submission->meta ?? [], [
                    'score_breakdown' => [
                        'version' => 1,
                        'earned' => $result['earned'],
                        'attainable' => $result['attainable'],
                        'blocks' => $result['blocks'],
                    ],
                ]);
                $submission->saveQuietly();

                $processed++;
            }

            $progress = $total > 0 ? (int) round($processed / $total * 100) : 100;
            $this->updateStatus('processing', $progress, $processed, $total);
        });

        // The analytics aggregate is cached, so the new scores would otherwise
        // take up to half an hour to appear.
        Cache::forget(ScoreTierResolver::statsCacheKey($this->form));

        $this->updateStatus('completed', 100, $processed, $total);

        Log::info("Score recalculation job {$this->jobId} completed", [
            'form_id' => $this->form->id,
            'submissions' => $processed,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->updateStatus('failed', 0, 0, 0, $exception->getMessage());

        Log::error("Score recalculation job {$this->jobId} failed", [
            'form_id' => $this->form->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function updateStatus(
        string $status,
        int $progress,
        int $processed,
        int $total,
        ?string $errorMessage = null
    ): void {
        $data = [
            'job_id' => $this->jobId,
            'status' => $status,
            'progress' => $progress,
            'form_id' => $this->form->id,
            'processed_submissions' => $processed,
            'total_submissions' => $total,
            'updated_at' => now()->toISOString(),
        ];

        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }

        Cache::put(self::cacheKey($this->jobId), $data, now()->addHours(2));
    }
}
