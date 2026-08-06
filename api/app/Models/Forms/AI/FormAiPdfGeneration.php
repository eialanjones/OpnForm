<?php

namespace App\Models\Forms\AI;

use App\Jobs\Form\GenerateAiPdf;
use App\Models\Forms\Form;
use App\Service\Storage\FileUploadPathService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One PDF a respondent asked the AI to write, from the moment the request is
 * accepted until the file is either downloaded, attached to a submission, or
 * pruned.
 */
class FormAiPdfGeneration extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'form_ai_pdf_generations';

    protected $fillable = [
        'form_id',
        'block_id',
        'token',
        'status',
        'answers',
        'file_name',
        'file_reference',
        'storage_uuid',
        'size_bytes',
        'error',
        'ip',
        'completed_at',
    ];

    /**
     * `answers` holds respondent data and `token` is a bearer secret; neither
     * belongs in a serialized response.
     */
    protected $hidden = [
        'token',
        'answers',
        'ip',
        'storage_uuid',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'size_bytes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $generation) {
            GenerateAiPdf::dispatch($generation);
        });

        static::deleted(function (self $generation) {
            // No-op once the form was submitted: the file has already been
            // moved out of tmp and next to the submission.
            $path = $generation->storagePath();
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * The generated file lives in tmp storage until the form is submitted, at
     * which point StoreFormSubmissionJob moves it next to the submission.
     */
    public function storagePath(): ?string
    {
        if (!$this->storage_uuid) {
            return null;
        }

        return FileUploadPathService::getTmpFileUploadPath($this->storage_uuid);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->file_reference !== null;
    }

    /**
     * Constant-time comparison so polling cannot be used as a timing oracle.
     */
    public function tokenMatches(?string $candidate): bool
    {
        return is_string($candidate) && hash_equals($this->token, $candidate);
    }

    public function fileStillExists(): bool
    {
        $path = $this->storagePath();

        return $path !== null && Storage::exists($path);
    }
}
