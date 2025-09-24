<?php

namespace Modules\Clocks\Support;

use Illuminate\Support\Arr;

class DeductionRuleEngine
{
    protected array $plan;

    protected array $defaultCategoryColors = [
        'lateness' => 'FFC7CE',
        'deduction' => 'FFC7CE',
        'shortfall' => 'FFC7CE',
        'default_shortfall' => 'FFC7CE',
        'overtime' => 'C6EFCE',
        'vacation' => 'BDD7EE',
        'issue' => 'FCE4D6',
        'bonus' => 'C6EFCE',
        'other' => 'D9D9D9',
    ];

    public function __construct(array $plan = [])
    {
        $this->plan = $plan;
    }

    public function evaluate(array $metrics, array &$state): array
    {
        $state['category_occurrence'] = $state['category_occurrence'] ?? [];

        $appliedRules = [];
        $dailyOccurrence = [];
        $rules = $this->plan['rules'] ?? [];

        foreach ($rules as $rule) {
            if (! $this->ruleMatches($rule, $metrics, $state, $dailyOccurrence)) {
                continue;
            }

            $penalty = $rule['penalty'] ?? [];
            $deductionMinutes = $this->penaltyToMinutes($penalty, $metrics);
            $monetaryAmount = null;

            if (($penalty['type'] ?? null) === 'amount') {
                $monetaryAmount = (float) ($penalty['value'] ?? 0);
            }

            $appliedRules[] = [
                'label' => $rule['label'] ?? ($rule['category'] ?? 'Rule'),
                'category' => $rule['category'] ?? 'other',
                'scope' => $rule['scope'] ?? null,
                'deduction_minutes' => max(0, (int) round($deductionMinutes)),
                'monetary_amount' => $monetaryAmount,
                'color' => $this->resolveColor($rule),
                'notes' => $rule['notes'] ?? null,
                'source' => $rule['source'] ?? null,
                'penalty' => $penalty,
            ];

            $categoryKey = $rule['category'] ?? 'other';
            $dailyOccurrence[$categoryKey] = ($dailyOccurrence[$categoryKey] ?? 0) + 1;

            if (! empty($rule['stop_processing'])) {
                break;
            }
        }

        if (empty($appliedRules)) {
            $fallbackMinutes = $this->computeDefaultShortfall($metrics, (int) ($this->plan['grace_minutes'] ?? 15));
            if ($fallbackMinutes > 0) {
                $appliedRules[] = [
                    'label' => 'Default shortfall deduction',
                    'category' => 'default_shortfall',
                    'scope' => 'daily',
                    'deduction_minutes' => $fallbackMinutes,
                    'monetary_amount' => null,
                    'color' => $this->defaultCategoryColors['default_shortfall'],
                    'notes' => null,
                    'source' => null,
                    'penalty' => [
                        'type' => 'fixed_minutes',
                        'value' => $fallbackMinutes,
                    ],
                ];
                $dailyOccurrence['default_shortfall'] = ($dailyOccurrence['default_shortfall'] ?? 0) + 1;
            }
        }

        foreach ($dailyOccurrence as $category => $count) {
            $state['category_occurrence'][$category] = ($state['category_occurrence'][$category] ?? 0) + $count;
        }

        $totalMinutes = 0;
        $totalAmount = 0.0;
        foreach ($appliedRules as $appliedRule) {
            $totalMinutes += $appliedRule['deduction_minutes'];
            $totalAmount += $appliedRule['monetary_amount'] ?? 0.0;
        }

        return [
            'deduction_minutes' => $totalMinutes,
            'applied_rules' => $appliedRules,
            'monetary_amount' => $totalAmount,
            'grace_minutes' => (int) ($this->plan['grace_minutes'] ?? 15),
        ];
    }

    protected function computeDefaultShortfall(array $metrics, int $graceMinutes): int
    {
        if (! empty($metrics['is_issue'])) {
            return 0;
        }

        $shortfall = (int) round($metrics['shortfall_minutes'] ?? 0);

        if ($shortfall <= $graceMinutes) {
            return 0;
        }

        return $shortfall;
    }

    protected function ruleMatches(array $rule, array $metrics, array $state, array $dailyOccurrence): bool
    {
        $when = $rule['when'] ?? [];
        if (! is_array($when)) {
            return true;
        }

        $category = $rule['category'] ?? 'other';

        foreach ($when as $key => $value) {
            switch ($key) {
                case 'minutes_late_gte':
                    if (($metrics['lateness_minutes_actual'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'minutes_late_lte':
                    if (($metrics['lateness_minutes_actual'] ?? 0) > (float) $value) {
                        return false;
                    }
                    break;
                case 'minutes_late_beyond_grace_gte':
                    if (($metrics['lateness_beyond_grace'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'shortfall_minutes_gte':
                    if (($metrics['shortfall_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'shortfall_minutes_lte':
                    if (($metrics['shortfall_minutes'] ?? 0) > (float) $value) {
                        return false;
                    }
                    break;
                case 'worked_minutes_gte':
                    if (($metrics['worked_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'worked_minutes_lte':
                    if (($metrics['worked_minutes'] ?? 0) > (float) $value) {
                        return false;
                    }
                    break;
                case 'attendance_overtime_minutes_gte':
                    if (($metrics['attendance_overtime_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'recorded_overtime_minutes_gte':
                case 'overtime_minutes_gte':
                    if (($metrics['recorded_ot_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'occurrence_number':
                    $target = (int) $value;
                    $current = ($state['category_occurrence'][$category] ?? 0) + ($dailyOccurrence[$category] ?? 0) + 1;
                    if ($current !== $target) {
                        return false;
                    }
                    break;
                case 'occurrence_every':
                    $every = max(1, (int) $value);
                    $current = ($state['category_occurrence'][$category] ?? 0) + ($dailyOccurrence[$category] ?? 0) + 1;
                    if ($current % $every !== 0) {
                        return false;
                    }
                    break;
                case 'issue':
                case 'is_issue':
                    if ((bool) ($metrics['is_issue'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'vacation':
                case 'is_vacation':
                    if ((bool) ($metrics['is_vacation'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'day_of_week':
                case 'day_of_week_in':
                    $day = strtolower($metrics['day_of_week'] ?? '');
                    $allowed = array_map(static function ($item) {
                        return strtolower($item);
                    }, (array) $value);
                    if ($day === '' || ! in_array($day, $allowed, true)) {
                        return false;
                    }
                    break;
                case 'date_equals':
                    if (($metrics['date'] ?? null) !== $value) {
                        return false;
                    }
                    break;
                default:
                    // Unknown condition keys are ignored to keep rules forward compatible.
                    break;
            }
        }

        return true;
    }

    protected function penaltyToMinutes(array $penalty, array $metrics): int
    {
        $type = $penalty['type'] ?? 'fixed_minutes';
        $value = $penalty['value'] ?? null;
        $requiredMinutes = (int) round($metrics['required_minutes'] ?? 0);
        $defaultDailyMinutes = (int) round($metrics['default_daily_minutes'] ?? ($requiredMinutes > 0 ? $requiredMinutes : 480));

        switch ($type) {
            case 'fraction_day':
                $base = $requiredMinutes > 0 ? $requiredMinutes : $defaultDailyMinutes;
                return (int) round($base * (float) ($value ?? 0));
            case 'day':
            case 'days':
                $base = $requiredMinutes > 0 ? $requiredMinutes : $defaultDailyMinutes;
                return (int) round($base * (float) ($value ?? 1));
            case 'fixed_hours':
                return (int) round(((float) ($value ?? 0)) * 60);
            case 'percentage_shortfall':
                $shortfall = (float) ($metrics['shortfall_minutes'] ?? 0);
                return (int) round($shortfall * ((float) ($value ?? 0) / 100));
            case 'fixed_minutes':
            default:
                return (int) round((float) ($value ?? 0));
        }
    }

    protected function resolveColor(array $rule): string
    {
        $color = $rule['color'] ?? null;
        if ($color) {
            return ltrim($color, '#');
        }

        $category = $rule['category'] ?? 'other';

        return $this->defaultCategoryColors[$category] ?? $this->defaultCategoryColors['other'];
    }
}
