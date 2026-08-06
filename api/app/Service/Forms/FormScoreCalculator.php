<?php

namespace App\Service\Forms;

use App\Models\Forms\Form;

/**
 * Computes the 0-10 score of a submission.
 *
 * Every scorable block holds a weight (0-10, step 0.1) and the weights of a
 * form add up to at most 10. A block earns its weight according to its scoring
 * mode. Blocks hidden by logic at submit time are excluded from both the earned
 * points and the attainable total, and the result is rescaled to 10 so that
 * respondents taking different branches stay comparable.
 */
class FormScoreCalculator
{
    /**
     * Reserved keys used to carry the score through the submission payload.
     * They can never collide with a property id, which is always a UUID.
     */
    public const SCORE_FIELD_ID = 'form_score';
    public const SCORE_TIER_FIELD_ID = 'form_score_tier';

    public const MAX_TOTAL_WEIGHT = 10.0;
    public const MAX_SCORE = 10.0;
    public const WEIGHT_STEP = 0.1;

    public const MODE_ANSWERED = 'answered';
    public const MODE_OPTIONS = 'options';
    public const MODE_PROPORTIONAL = 'proportional';
    public const MODE_LOGIC = 'logic';

    public const MODES = [
        self::MODE_ANSWERED,
        self::MODE_OPTIONS,
        self::MODE_PROPORTIONAL,
        self::MODE_LOGIC,
    ];

    /**
     * `radio` and `toggle_switch` never reach the backend: the editor stores
     * their `actual_input`, so only `select` and `checkbox` exist here.
     */
    public const OPTION_MODE_TYPES = ['select', 'multi_select', 'checkbox'];

    public const PROPORTIONAL_MODE_TYPES = ['number', 'rating', 'scale', 'slider'];

    public const ACTION_AWARD_SCORE = 'award-score';
    public const ACTION_ZERO_SCORE = 'zero-score';

    public function __construct(private Form $form)
    {
    }

    /**
     * @param  array  $formData  Map of property id => submitted value.
     * @return array{score: ?float, earned: float, attainable: float, blocks: array<string, float>}
     */
    public static function compute(Form $form, array $formData): array
    {
        return (new self($form))->calculate($formData);
    }

    /**
     * @return array{score: ?float, earned: float, attainable: float, blocks: array<string, float>}
     */
    public function calculate(array $formData): array
    {
        $earned = 0.0;
        $attainable = 0.0;
        $blocks = [];

        foreach ($this->form->properties ?? [] as $property) {
            if (!is_array($property)) {
                continue;
            }

            $type = $property['type'] ?? null;
            if (!is_string($type) || str_starts_with($type, 'nf-')) {
                continue;
            }

            $weight = (float) ($property['scoring']['weight'] ?? 0);
            if ($weight <= 0) {
                continue;
            }

            if ($this->isHidden($property, $formData)) {
                continue;
            }

            $points = $this->pointsFor($property, $weight, $formData);

            $attainable += $weight;
            $earned += $points;
            $blocks[(string) ($property['id'] ?? '')] = round($points, 2);
        }

        $score = $attainable <= 0
            ? null
            : round(min($earned, $attainable) / $attainable * self::MAX_SCORE, 1);

        return [
            'score' => $score,
            'earned' => round($earned, 2),
            'attainable' => round($attainable, 2),
            'blocks' => $blocks,
        ];
    }

    private function isHidden(array $property, array $formData): bool
    {
        try {
            return FormLogicPropertyResolver::isHidden($property, $formData);
        } catch (\Throwable) {
            return (bool) ($property['hidden'] ?? false);
        }
    }

    private function pointsFor(array $property, float $weight, array $formData): float
    {
        // A firing zero-score rule wins over every mode, but the block stays in
        // the attainable total: it was asked and it earned nothing.
        if ($this->logicActionFires($property, self::ACTION_ZERO_SCORE, $formData)) {
            return 0.0;
        }

        $mode = $property['scoring']['mode'] ?? self::MODE_ANSWERED;

        // Logic scoring deliberately ignores whether the block itself was
        // answered: the rule may depend on another block's value.
        if ($mode === self::MODE_LOGIC) {
            return $this->logicActionFires($property, self::ACTION_AWARD_SCORE, $formData) ? $weight : 0.0;
        }

        $fieldId = (string) ($property['id'] ?? '');
        $value = $formData[$fieldId] ?? null;

        // A checkbox scored per option answers with either state, so an
        // explicit "unchecked" is a real answer rather than a missing one.
        $unansweredStillScores = $mode === self::MODE_OPTIONS
            && ($property['type'] ?? null) === 'checkbox'
            && array_key_exists($fieldId, $formData);

        if (!$unansweredStillScores && !$this->isAnswered($property, $value)) {
            return 0.0;
        }

        return match ($mode) {
            self::MODE_OPTIONS => $this->optionPoints($property, $weight, $value),
            self::MODE_PROPORTIONAL => $this->proportionalPoints($property, $weight, $value),
            default => $weight,
        };
    }

    private function logicActionFires(array $property, string $action, array $formData): bool
    {
        $logic = $property['logic'] ?? null;

        if (!is_array($logic) || empty($logic['conditions'])) {
            return false;
        }

        if (!in_array($action, $logic['actions'] ?? [], true)) {
            return false;
        }

        try {
            return FormLogicConditionChecker::conditionsMet($logic['conditions'], $formData);
        } catch (\Throwable) {
            // A malformed legacy rule must never break a submission.
            return false;
        }
    }

    /**
     * Option points are keyed by option name: that is what the editor writes as
     * the option id and what the respondent's answer actually contains.
     */
    private function optionPoints(array $property, float $weight, mixed $value): float
    {
        $points = $property['scoring']['option_points'] ?? null;
        if (!is_array($points) || $points === []) {
            return 0.0;
        }

        $total = match ($property['type']) {
            'multi_select' => array_reduce(
                (array) $value,
                fn (float $sum, $selected) => $sum + (float) ($points[(string) $selected] ?? 0),
                0.0
            ),
            'checkbox' => (float) ($points[$value ? 'true' : 'false'] ?? 0),
            default => (float) ($points[(string) $value] ?? 0),
        };

        return max(0.0, min($total, $weight));
    }

    private function proportionalPoints(array $property, float $weight, mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        [$min, $max] = $this->proportionalBounds($property);
        if ($max <= $min) {
            return 0.0;
        }

        $clamped = max($min, min((float) $value, $max));

        // Subtracting the minimum keeps the floor at zero: on a 1-5 scale the
        // worst possible answer must not already be worth 20% of the weight.
        return round(($clamped - $min) / ($max - $min) * $weight, 2);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function proportionalBounds(array $property): array
    {
        $type = $property['type'] ?? null;

        $min = match ($type) {
            'scale' => (float) ($property['scale_min_value'] ?? 1),
            'slider' => (float) ($property['slider_min_value'] ?? 0),
            default => 0.0,
        };

        // Number blocks carry no bounds of their own, so the owner sets one.
        $override = $property['scoring']['proportional_max'] ?? null;
        if (is_numeric($override)) {
            return [$min, (float) $override];
        }

        $max = match ($type) {
            'rating' => (float) ($property['rating_max_value'] ?? 5),
            'scale' => (float) ($property['scale_max_value'] ?? 5),
            'slider' => (float) ($property['slider_max_value'] ?? 50),
            default => 0.0,
        };

        return [$min, $max];
    }

    private function isAnswered(array $property, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return match ($property['type'] ?? null) {
            'checkbox' => $value === true || $value === 1 || $value === '1' || $value === 'true',
            'number', 'rating', 'scale', 'slider' => is_numeric($value),
            'multi_select', 'files', 'matrix' => $this->hasFilledEntry($value),
            'date' => $this->dateIsAnswered($value),
            // Mirrors FormLogicConditionChecker::checkPaid(): an abandoned
            // payment must not earn points.
            'payment' => is_string($value) && str_starts_with($value, 'pi_'),
            default => is_array($value) ? $this->hasFilledEntry($value) : trim((string) $value) !== '',
        };
    }

    private function hasFilledEntry(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $entry) {
            if ($entry !== null && $entry !== '' && $entry !== []) {
                return true;
            }
        }

        return false;
    }

    private function dateIsAnswered(mixed $value): bool
    {
        // Date-range blocks store [from, to].
        if (is_array($value)) {
            return isset($value[0]) && trim((string) $value[0]) !== '';
        }

        return trim((string) $value) !== '';
    }
}
