<?php

namespace App\Service\Forms;

use App\Models\Forms\Form;

/**
 * Resolves a submission score into one of the form's named score tiers.
 *
 * Tiers are never stored on the submission: they are derived on read from
 * `form.settings.score_tiers`, so renaming a tier updates the whole history
 * without recalculating anything.
 */
class ScoreTierResolver
{
    /**
     * Used until the form owner saves their own tiers.
     */
    public const DEFAULT_TIERS = [
        ['label' => 'Frio', 'from' => 0, 'color' => '#3b82f6'],
        ['label' => 'Morno', 'from' => 5, 'color' => '#f59e0b'],
        ['label' => 'Quente', 'from' => 8, 'color' => '#ef4444'],
    ];

    /**
     * The form's tiers, sorted ascending by threshold.
     *
     * @return array<int, array{label: string, from: float, color: ?string}>
     */
    public static function tiers(Form $form): array
    {
        $configured = $form->settings['score_tiers'] ?? null;

        $tiers = is_array($configured)
            ? array_values(array_filter(
                $configured,
                fn ($tier) => is_array($tier) && isset($tier['from']) && is_numeric($tier['from'])
            ))
            : [];

        if ($tiers === []) {
            $tiers = self::DEFAULT_TIERS;
        }

        usort($tiers, fn ($a, $b) => $a['from'] <=> $b['from']);

        return array_map(fn ($tier) => [
            'label' => (string) ($tier['label'] ?? ''),
            'from' => (float) $tier['from'],
            'color' => $tier['color'] ?? null,
        ], $tiers);
    }

    /**
     * The tiers as half-open [from, to) intervals, for range counting.
     *
     * @return array<int, array{label: string, from: float, to: float, color: ?string}>
     */
    public static function bounds(Form $form): array
    {
        $tiers = self::tiers($form);

        return array_map(function ($tier, $index) use ($tiers) {
            return [
                'label' => $tier['label'],
                'color' => $tier['color'],
                'from' => $tier['from'],
                // The last interval is nudged past the maximum so a perfect score lands in it.
                'to' => isset($tiers[$index + 1])
                    ? $tiers[$index + 1]['from']
                    : FormScoreCalculator::MAX_SCORE + 0.001,
            ];
        }, $tiers, array_keys($tiers));
    }

    /**
     * @return array{label: string, from: float, color: ?string}|null
     */
    public static function resolve(Form $form, ?float $score): ?array
    {
        if ($score === null) {
            return null;
        }

        $match = null;
        foreach (self::tiers($form) as $tier) {
            if ($score >= $tier['from']) {
                $match = $tier;
            }
        }

        return $match;
    }

    /**
     * Cache key for the analytics aggregate.
     *
     * Tiers are derived on read, so the key has to move with them: otherwise
     * editing a tier appears to do nothing until the cache expires.
     */
    public static function statsCacheKey(Form $form): string
    {
        return 'form_stats_score_' . $form->id . '_' . md5(json_encode(self::bounds($form)));
    }

    public static function label(Form $form, ?float $score): ?string
    {
        $tier = self::resolve($form, $score);

        return $tier === null ? null : $tier['label'];
    }
}
