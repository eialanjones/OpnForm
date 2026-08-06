<?php

namespace App\Rules\PropertyValidators;

use App\Service\Forms\FormScoreCalculator;

/**
 * Validates the scoring configuration of form properties.
 *
 * Runs per property, but also owns the one cross-property rule: the weights of
 * a form must add up to at most 10.
 */
class ScoringPropertyValidator implements PropertyValidatorInterface
{
    private const LAYOUT_PREFIX = 'nf-';

    /**
     * Memoised across the properties of a single request so the budget message
     * is emitted once instead of once per weighted block.
     */
    private ?float $totalWeight = null;

    private ?int $firstScoredIndex = null;

    public function validate(array $property, int $index, array $context): array
    {
        $errors = [];
        $scoring = $property['scoring'] ?? null;

        if (!is_array($scoring) || $scoring === []) {
            return $errors;
        }

        $type = $property['type'] ?? null;
        $weight = $scoring['weight'] ?? 0;

        if (is_string($type) && str_starts_with($type, self::LAYOUT_PREFIX)) {
            if (is_numeric($weight) && (float) $weight > 0) {
                $errors['scoring.weight'] = 'Layout blocks cannot be scored.';
            }

            return $errors;
        }

        // Checked first so an over-budget form always reports it, even when the
        // block carrying the message has other problems too.
        $budgetError = $this->validateBudget($index, $context);
        if ($budgetError !== null) {
            $errors['scoring'] = $budgetError;
        }

        if (!is_numeric($weight)) {
            $errors['scoring.weight'] = 'The block weight must be a number between 0 and ' . (int) FormScoreCalculator::MAX_TOTAL_WEIGHT . '.';
            return $errors;
        }

        $weight = (float) $weight;

        if ($weight < 0 || $weight > FormScoreCalculator::MAX_TOTAL_WEIGHT) {
            $errors['scoring.weight'] = 'The block weight must be a number between 0 and ' . (int) FormScoreCalculator::MAX_TOTAL_WEIGHT . '.';
            return $errors;
        }

        // Compare in tenths: fmod() never cleanly reports 0 on binary floats.
        if (abs(round($weight * 10) - $weight * 10) > 1e-9) {
            $errors['scoring.weight'] = 'The block weight must use steps of ' . FormScoreCalculator::WEIGHT_STEP . '.';
            return $errors;
        }

        $modeErrors = $this->validateMode($property, $scoring, $weight);
        if ($modeErrors !== []) {
            return array_merge($errors, $modeErrors);
        }

        return array_merge($errors, $this->validateOptionPoints($scoring, $weight));
    }

    private function validateMode(array $property, array $scoring, float $weight): array
    {
        $mode = $scoring['mode'] ?? FormScoreCalculator::MODE_ANSWERED;
        $type = $property['type'] ?? null;

        if (!in_array($mode, FormScoreCalculator::MODES, true)) {
            return ['scoring.mode' => 'Invalid scoring mode.'];
        }

        if ($mode === FormScoreCalculator::MODE_OPTIONS && !in_array($type, FormScoreCalculator::OPTION_MODE_TYPES, true)) {
            return ['scoring.mode' => 'Option scoring is not available for this block type.'];
        }

        if ($mode === FormScoreCalculator::MODE_PROPORTIONAL) {
            if (!in_array($type, FormScoreCalculator::PROPORTIONAL_MODE_TYPES, true)) {
                return ['scoring.mode' => 'Proportional scoring is not available for this block type.'];
            }

            // Number blocks carry no bounds of their own.
            if ($type === 'number') {
                $max = $scoring['proportional_max'] ?? null;
                if (!is_numeric($max) || (float) $max <= 0) {
                    return ['scoring.proportional_max' => 'A maximum value is required to score this number block proportionally.'];
                }
            }
        }

        if ($mode === FormScoreCalculator::MODE_LOGIC && $weight > 0) {
            $logic = $property['logic'] ?? null;
            $hasRule = is_array($logic)
                && !empty($logic['conditions'])
                && in_array(FormScoreCalculator::ACTION_AWARD_SCORE, $logic['actions'] ?? [], true);

            if (!$hasRule) {
                return ['scoring.mode' => 'Logic scoring requires a logic rule using the award score action.'];
            }
        }

        return [];
    }

    private function validateOptionPoints(array $scoring, float $weight): array
    {
        $points = $scoring['option_points'] ?? null;

        if ($points === null) {
            return [];
        }

        if (!is_array($points)) {
            return ['scoring.option_points' => 'Option points must be a list of values.'];
        }

        foreach ($points as $value) {
            if (!is_numeric($value) || (float) $value < 0 || (float) $value > $weight) {
                return ['scoring.option_points' => 'Option points must be between 0 and the block weight.'];
            }
        }

        return [];
    }

    private function validateBudget(int $index, array $context): ?string
    {
        $this->ensureTotals($context['properties'] ?? []);

        if ($index !== $this->firstScoredIndex) {
            return null;
        }

        // Round before comparing: ten weights of 0.1 sum to 10.000000000000002.
        if (round($this->totalWeight, 1) <= FormScoreCalculator::MAX_TOTAL_WEIGHT) {
            return null;
        }

        return 'The sum of all block weights must not exceed '
            . (int) FormScoreCalculator::MAX_TOTAL_WEIGHT
            . ' (currently ' . round($this->totalWeight, 1) . ').';
    }

    private function ensureTotals(array $properties): void
    {
        if ($this->totalWeight !== null) {
            return;
        }

        $this->totalWeight = 0.0;

        foreach ($properties as $propertyIndex => $property) {
            if (!is_array($property)) {
                continue;
            }

            $type = $property['type'] ?? null;
            if (is_string($type) && str_starts_with($type, self::LAYOUT_PREFIX)) {
                continue;
            }

            $weight = $property['scoring']['weight'] ?? 0;
            if (!is_numeric($weight) || (float) $weight <= 0) {
                continue;
            }

            $this->totalWeight += (float) $weight;
            $this->firstScoredIndex ??= $propertyIndex;
        }
    }
}
