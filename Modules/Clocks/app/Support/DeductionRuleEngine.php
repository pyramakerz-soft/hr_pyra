<?php

namespace Modules\Clocks\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class DeductionRuleEngine
{
    protected array $plan;

    protected array $defaultCategoryColors = [
        'lateness' => 'F8D5D8',
        'deduction' => 'F7E0C7',
        'shortfall' => 'F3E5AB',
        'default_shortfall' => 'F9D9B5',
        'overtime' => 'C8E6C9',
        'vacation' => 'AED9F4',
        'issue' => 'F6C1D1',
        'bonus' => 'D7C7ED',
        'other' => 'D0D7DD',
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
            $category = $rule['category'] ?? 'other';
            $currentOccurrence = ($state['category_occurrence'][$category] ?? 0) + ($dailyOccurrence[$category] ?? 0) + 1;

            if (! $this->ruleMatches($rule, $metrics, $state, $dailyOccurrence, $currentOccurrence, $category)) {
                continue;
            }

            $penalty = $rule['penalty'] ?? [];
            $deductionMinutes = $this->penaltyToMinutes($penalty, $metrics, $currentOccurrence);
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
                'notes' => $this->renderNotes($rule['notes'] ?? null, $metrics, $currentOccurrence),
                'source' => $rule['source'] ?? null,
                'penalty' => $penalty,
                'occurrence' => $currentOccurrence,
                'template_key' => $rule['template_key'] ?? null,
                'template_id' => $rule['template_id'] ?? null,
                'meta' => isset($rule['meta']) && is_array($rule['meta'])
                    ? $rule['meta']
                    : null,
            ];

            $dailyOccurrence[$category] = ($dailyOccurrence[$category] ?? 0) + 1;

            if (! empty($rule['stop_processing'])) {
                break;
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

    protected function ruleMatches(array $rule, array $metrics, array $state, array $dailyOccurrence, int $currentOccurrence, string $category): bool
    {
        $when = $rule['when'] ?? [];
        if (! is_array($when)) {
            return true;
        }

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
                case 'minutes_late_gt':
                    if (($metrics['lateness_minutes_actual'] ?? 0) <= (float) $value) {
                        return false;
                    }
                    break;
                case 'minutes_late_lt':
                    if (($metrics['lateness_minutes_actual'] ?? 0) >= (float) $value) {
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
                    if ($currentOccurrence !== $target) {
                        return false;
                    }
                    break;
                case 'occurrence_every':
                    $every = max(1, (int) $value);
                    if ($currentOccurrence % $every !== 0) {
                        return false;
                    }
                    break;
                case 'occurrence_gte':
                    if ($currentOccurrence < (int) $value) {
                        return false;
                    }
                    break;
                case 'occurrence_gt':
                    if ($currentOccurrence <= (int) $value) {
                        return false;
                    }
                    break;
                case 'occurrence_lte':
                    if ($currentOccurrence > (int) $value) {
                        return false;
                    }
                    break;
                case 'occurrence_lt':
                    if ($currentOccurrence >= (int) $value) {
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
                case 'location_type_in':
                    $allowedLocations = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $actualLocations = array_filter(array_map('strtolower', (array) ($metrics['location_types'] ?? [])));
                    if (empty($allowedLocations)) {
                        return false;
                    }
                    if (empty($actualLocations) || empty(array_intersect($allowedLocations, $actualLocations))) {
                        return false;
                    }
                    break;
                case 'location_type_not_in':
                    $blockedLocations = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $actualLocations = array_filter(array_map('strtolower', (array) ($metrics['location_types'] ?? [])));
                    if (! empty($blockedLocations) && ! empty(array_intersect($blockedLocations, $actualLocations))) {
                        return false;
                    }
                    break;
                case 'work_type_in':
                    $allowedWorkTypes = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $userWorkTypes = array_filter(array_map('strtolower', (array) ($metrics['user_work_types'] ?? [])));
                    if (empty($allowedWorkTypes)) {
                        return false;
                    }
                    if (empty($userWorkTypes) || empty(array_intersect($allowedWorkTypes, $userWorkTypes))) {
                        return false;
                    }
                    break;
                case 'work_type_not_in':
                    $blockedWorkTypes = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $userWorkTypes = array_filter(array_map('strtolower', (array) ($metrics['user_work_types'] ?? [])));
                    if (! empty($blockedWorkTypes) && ! empty(array_intersect($blockedWorkTypes, $userWorkTypes))) {
                        return false;
                    }
                    break;
                case 'expected_schedule_source_in':
                    $allowedSources = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $actualSource = strtolower((string) ($metrics['expected_schedule_source'] ?? ''));
                    if (empty($allowedSources) || $actualSource === '' || ! in_array($actualSource, $allowedSources, true)) {
                        return false;
                    }
                    break;
                case 'expected_schedule_source_not_in':
                    $blockedSources = array_filter(array_map('strtolower', Arr::wrap($value)));
                    $actualSource = strtolower((string) ($metrics['expected_schedule_source'] ?? ''));
                    if ($actualSource !== '' && ! empty($blockedSources) && in_array($actualSource, $blockedSources, true)) {
                        return false;
                    }
                    break;
                case 'expected_minutes_gte':
                    if (($metrics['expected_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'expected_minutes_gt':
                    if (($metrics['expected_minutes'] ?? 0) <= (float) $value) {
                        return false;
                    }
                    break;
                case 'expected_minutes_lte':
                    if (($metrics['expected_minutes'] ?? 0) > (float) $value) {
                        return false;
                    }
                    break;
                case 'expected_minutes_lt':
                    if (($metrics['expected_minutes'] ?? 0) >= (float) $value) {
                        return false;
                    }
                    break;
                case 'first_clock_in_gte':
                case 'first_clock_in_gt':
                case 'first_clock_in_lte':
                case 'first_clock_in_lt':
                    $clockInMinutes = $metrics['first_clock_in_minutes'] ?? null;
                    if ($clockInMinutes === null) {
                        return false;
                    }
                    $threshold = $this->normalizeTimeToMinutes($value);
                    if ($threshold === null) {
                        return false;
                    }

                    switch ($key) {
                        case 'first_clock_in_gte':
                            if ($clockInMinutes < $threshold) {
                                return false;
                            }
                            break;
                        case 'first_clock_in_gt':
                            if ($clockInMinutes <= $threshold) {
                                return false;
                            }
                            break;
                        case 'first_clock_in_lte':
                            if ($clockInMinutes > $threshold) {
                                return false;
                            }
                            break;
                        case 'first_clock_in_lt':
                            if ($clockInMinutes >= $threshold) {
                                return false;
                            }
                            break;
                    }
                    break;
                case 'first_clock_in_between':
                    $clockInMinutes = $metrics['first_clock_in_minutes'] ?? null;
                    if ($clockInMinutes === null) {
                        return false;
                    }

                    $range = Arr::wrap($value);
                    if (count($range) < 2) {
                        return false;
                    }

                    $startMinutes = $this->normalizeTimeToMinutes($range[0]);
                    $endMinutes = $this->normalizeTimeToMinutes($range[1]);
                    if ($startMinutes === null || $endMinutes === null) {
                        return false;
                    }

                    if ($startMinutes <= $endMinutes) {
                        if ($clockInMinutes < $startMinutes || $clockInMinutes > $endMinutes) {
                            return false;
                        }
                    } else {
                        if ($clockInMinutes > $endMinutes && $clockInMinutes < $startMinutes) {
                            return false;
                        }
                    }
                    break;
                case 'worked_minutes_meets_expected':
                    if ((bool) ($metrics['worked_minutes_meets_expected'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'worked_minutes_meets_required':
                    if ((bool) ($metrics['worked_minutes_meets_required'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'is_part_time':
                    if ((bool) ($metrics['is_part_time'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'overtime_locked':
                    if ((bool) ($metrics['overtime_locked'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'part_time_exceeds_month_limit':
                    if ((bool) ($metrics['part_time_exceeds_month_limit'] ?? false) !== (bool) $value) {
                        return false;
                    }
                    break;
                case 'part_time_excess_minutes_gte':
                    if (($metrics['part_time_excess_minutes'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_excess_minutes_gt':
                    if (($metrics['part_time_excess_minutes'] ?? 0) <= (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_excess_minutes_lte':
                    if (($metrics['part_time_excess_minutes'] ?? 0) > (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_excess_minutes_lt':
                    if (($metrics['part_time_excess_minutes'] ?? 0) >= (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_monthly_worked_before_gte':
                    if (($metrics['part_time_monthly_worked_before'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_monthly_worked_after_gte':
                    if (($metrics['part_time_monthly_worked_after'] ?? 0) < (float) $value) {
                        return false;
                    }
                    break;
                case 'part_time_monthly_limit_minutes_gte':
                    if (($metrics['part_time_monthly_limit_minutes'] ?? 0) < (float) $value) {
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

    protected function normalizeTimeToMinutes($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) round((float) $value);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $time = Carbon::createFromFormat($format, $value);
                if ($time !== false) {
                    return ($time->hour * 60) + $time->minute;
                }
            } catch (\Throwable $throwable) {
                // Ignore and try next format.
            }
        }

        try {
            $time = Carbon::parse($value);
            return ($time->hour * 60) + $time->minute;
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    protected function penaltyToMinutes(array $penalty, array $metrics, int $occurrence = 1): int
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
            case 'lateness_actual':
            case 'lateness_minutes':
            case 'lateness_minutes_actual':
                return max(0, (int) round($metrics['lateness_minutes_actual'] ?? 0));
            case 'lateness_beyond_grace':
                return max(0, (int) round($metrics['lateness_beyond_grace'] ?? 0));
            case 'metric_minutes':
                $metricKey = is_string($penalty['metric'] ?? null) ? $penalty['metric'] : null;
                if ($metricKey === null) {
                    return 0;
                }
                $metricValue = $metrics[$metricKey] ?? 0;
                if ($metricValue === null) {
                    return 0;
                }
                return (int) round((float) $metricValue);
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

    protected function renderNotes($notes, array $metrics, int $occurrence): ?string
    {
        if ($notes === null) {
            return null;
        }

        if (! is_string($notes)) {
            return $notes;
        }

        $resolved = preg_replace_callback('/\{\{\s*metrics\.([a-z0-9_]+)\s*\}\}/i', function ($matches) use ($metrics) {
            $key = $matches[1] ?? null;
            if (! $key) {
                return '';
            }

            $value = Arr::get($metrics, $key);

            if (is_array($value)) {
                return implode(', ', array_map(static function ($item) {
                    return is_scalar($item) ? (string) $item : json_encode($item);
                }, $value));
            }

            return is_scalar($value) ? (string) $value : '';
        }, $notes);

        $resolved = preg_replace_callback('/\{\{\s*occurrence\s*\}\}/i', static function () use ($occurrence) {
            return (string) max(0, $occurrence);
        }, $resolved);

        return $resolved;
    }
}
