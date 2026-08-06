<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the ordered list of score tiers stored in `form.settings`.
 *
 * Each tier only carries the threshold it starts at; it runs up to the next
 * tier. That makes the ordering rules below the whole contract.
 */
class ScoreTiersRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === []) {
            return;
        }

        if (!is_array($value)) {
            $fail('The score tiers must be a list.');
            return;
        }

        $previousFrom = null;
        $labels = [];

        foreach (array_values($value) as $index => $tier) {
            if (!is_array($tier) || !isset($tier['from']) || !is_numeric($tier['from'])) {
                $fail('Each score tier must have a numeric starting value.');
                return;
            }

            $from = (float) $tier['from'];

            // Without a tier starting at zero the lowest scores match nothing.
            if ($index === 0 && $from !== 0.0) {
                $fail('The first score tier must start at 0.');
                return;
            }

            if ($previousFrom !== null && $from <= $previousFrom) {
                $fail('Score tiers must start at increasing values.');
                return;
            }

            $label = trim((string) ($tier['label'] ?? ''));
            if (in_array($label, $labels, true)) {
                $fail('Score tiers must have distinct names.');
                return;
            }

            $labels[] = $label;
            $previousFrom = $from;
        }
    }
}
