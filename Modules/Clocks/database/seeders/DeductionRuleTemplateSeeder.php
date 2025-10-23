<?php

namespace Modules\Clocks\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Modules\Clocks\Models\DeductionRuleTemplate;

class DeductionRuleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'academic_bus_users',
                'name' => 'Academic Bus Users (No Deduction)',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Track lateness minutes for academic staff who use the bus without applying deductions.',
                'rule' => [
                    'label' => 'Academic Bus: Track Lateness',
                    'category' => 'lateness',
                    'scope' => 'daily',
                    'when' => [
                        'minutes_late_gte' => 1,
                    ],
                    'penalty' => [
                        'type' => 'fixed_minutes',
                        'value' => 0,
                    ],
                    'notes' => 'Late by {{metrics.lateness_minutes_actual}} minutes (no deduction applied).',
                    'color' => '#BDD7EE',
                    'stop_processing' => false,
                    'meta' => [
                        'template_key' => 'academic_bus_users',
                        'record_only' => true,
                    ],
                ],
            ],
            [
                'key' => 'academic_no_bus_users',
                'name' => 'Academic (No Bus) Escalating Lateness',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Escalating deduction for academic staff without bus service based on lateness occurrences per month.',
                'rule' => [
                    [
                        'label' => 'Academic No-Bus Lateness (Occurrence 1)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 1,
                            'occurrence_number' => 1,
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 0.25,
                        ],
                        'notes' => 'Occurrence {{occurrence}}: deduct 0.25 day.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'academic_no_bus_users',
                            'sequence_step' => 1,
                        ],
                    ],
                    [
                        'label' => 'Academic No-Bus Lateness (Occurrence 2)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 1,
                            'occurrence_number' => 2,
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 0.5,
                        ],
                        'notes' => 'Occurrence {{occurrence}}: deduct 0.5 day.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'academic_no_bus_users',
                            'sequence_step' => 2,
                        ],
                    ],
                    [
                        'label' => 'Academic No-Bus Lateness (Occurrence 3)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 1,
                            'occurrence_number' => 3,
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 1,
                        ],
                        'notes' => 'Occurrence {{occurrence}}: deduct 1 day.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'academic_no_bus_users',
                            'sequence_step' => 3,
                        ],
                    ],
                    [
                        'label' => 'Academic No-Bus Lateness (Occurrence 4)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 1,
                            'occurrence_number' => 4,
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 2,
                        ],
                        'notes' => 'Occurrence {{occurrence}}: deduct 2 days.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'academic_no_bus_users',
                            'sequence_step' => 4,
                        ],
                    ],
                    [
                        'label' => 'Academic No-Bus Lateness (Occurrence 5+ )',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 1,
                            'occurrence_gte' => 5,
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 4,
                        ],
                        'notes' => 'Occurrence {{occurrence}}: deduct 4 days (cap).',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'academic_no_bus_users',
                            'sequence_step' => 5,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'academic_far_users',
                'name' => 'Academic Far Users (Minute-for-Minute)',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Deduct lateness minute-for-minute for academic staff based far from campus.',
                'rule' => [
                    'label' => 'Academic Far Users: Minute-for-Minute',
                    'category' => 'lateness',
                    'scope' => 'daily',
                    'when' => [
                        'minutes_late_gte' => 1,
                    ],
                    'penalty' => [
                        'type' => 'lateness_actual',
                    ],
                    'notes' => 'Deduct {{metrics.lateness_minutes_actual}} minutes for lateness.',
                    'color' => '#F7CAAC',
                    'stop_processing' => true,
                    'meta' => [
                        'template_key' => 'academic_far_users',
                        'deduction_mode' => 'minute_for_minute',
                    ],
                ],
            ],
            [
                'key' => 'company_late',
                'name' => 'Company Late (Hybrid)',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Company-wide lateness rule: 1 hour deduction for 15-60 minutes late, then minute-for-minute beyond that.',
                'rule' => [
                    [
                        'label' => 'Company Late: 15-60 minutes -> 1 hour',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 15,
                            'minutes_late_lte' => 60,
                        ],
                        'penalty' => [
                            'type' => 'fixed_hours',
                            'value' => 1,
                        ],
                        'notes' => 'Late by {{metrics.lateness_minutes_actual}} minutes => deduct 1 hour.',
                        'color' => '#F4B183',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'company_late',
                            'band' => '15-60',
                        ],
                    ],
                    [
                        'label' => 'Company Late: 60+ minutes -> minute-for-minute',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'minutes_late_gte' => 61,
                        ],
                        'penalty' => [
                            'type' => 'lateness_actual',
                        ],
                        'notes' => 'Late by {{metrics.lateness_minutes_actual}} minutes => deduct actual lateness.',
                        'color' => '#F4B183',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'company_late',
                            'band' => '60_plus',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'company_late_two',
                'name' => 'Company Late (Custom Placeholder)',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Placeholder template for additional company lateness logic. Edit before assigning.',
                'rule' => [
                    'label' => 'Company Late Rule (Customize Me)',
                    'category' => 'lateness',
                    'scope' => 'daily',
                    'when' => [
                        'minutes_late_gte' => 1,
                    ],
                    'penalty' => [
                        'type' => 'fixed_minutes',
                        'value' => 0,
                    ],
                    'notes' => 'Customize this template before assigning to departments.',
                    'color' => '#D9D9D9',
                    'stop_processing' => false,
                    'meta' => [
                        'template_key' => 'company_late_two',
                        'placeholder' => true,
                    ],
                ],
                'is_active' => false,
            ],
            [
                'key' => 'flexible_arrival_window',
                'name' => 'Flexible Arrival Window (7AM-11AM)',
                'category' => 'lateness',
                'scope' => 'daily',
                'description' => 'Allow arrivals between 7:00 and 10:00 with no deduction if the full shift is worked, deduct 1 hour when arriving between 10:00-11:00, and half-day after 11:00.',
                'rule' => [
                    [
                        'label' => 'Flexible Arrival: 07:00-10:00 (Full Shift)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'first_clock_in_between' => ['07:00', '10:00'],
                            'worked_minutes_meets_expected' => true,
                        ],
                        'penalty' => [
                            'type' => 'fixed_minutes',
                            'value' => 0,
                        ],
                        'notes' => 'Clocked in at {{metrics.first_clock_in_time}} and completed expected minutes => no deduction.',
                        'color' => '#C6EFCE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'flexible_arrival_window',
                            'band' => '07-10_full_shift',
                        ],
                    ],
                    [
                        'label' => 'Flexible Arrival: 10:00-11:00 (1 Hour)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'first_clock_in_gt' => '10:00',
                            'first_clock_in_lte' => '11:00',
                        ],
                        'penalty' => [
                            'type' => 'fixed_hours',
                            'value' => 1,
                        ],
                        'notes' => 'Clocked in at {{metrics.first_clock_in_time}} => deduct 1 hour.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'flexible_arrival_window',
                            'band' => '10-11_one_hour',
                        ],
                    ],
                    [
                        'label' => 'Flexible Arrival: After 11:00 (Half Day)',
                        'category' => 'lateness',
                        'scope' => 'daily',
                        'when' => [
                            'first_clock_in_gt' => '11:00',
                        ],
                        'penalty' => [
                            'type' => 'fraction_day',
                            'value' => 0.5,
                        ],
                        'notes' => 'Clocked in at {{metrics.first_clock_in_time}} => half-day deduction.',
                        'color' => '#FFC7CE',
                        'stop_processing' => true,
                        'meta' => [
                            'template_key' => 'flexible_arrival_window',
                            'band' => 'after-11_half_day',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'part_time_monthly_limit',
                'name' => 'Part-Time Monthly Limit',
                'category' => 'deduction',
                'scope' => 'daily',
                'description' => 'Monitors part-time employees against their monthly hour cap and deducts excess minutes.',
                'rule' => [
                    'label' => 'Part-Time Monthly Cap Exceeded',
                    'category' => 'deduction',
                    'scope' => 'daily',
                    'when' => [
                        'is_part_time' => true,
                        'part_time_exceeds_month_limit' => true,
                    ],
                    'penalty' => [
                        'type' => 'metric_minutes',
                        'metric' => 'part_time_excess_minutes',
                    ],
                    'notes' => 'Exceeded monthly limit by {{metrics.part_time_excess_minutes}} minutes (limit {{metrics.part_time_monthly_limit_minutes}}).',
                    'color' => '#FFC7CE',
                    'stop_processing' => false,
                    'meta' => [
                        'template_key' => 'part_time_monthly_limit',
                    ],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $payload = Arr::only($templateData, [
                'name',
                'category',
                'scope',
                'description',
                'rule',
                'is_active',
            ]);

            if (! array_key_exists('is_active', $payload)) {
                $payload['is_active'] = true;
            }

            DeductionRuleTemplate::updateOrCreate(
                ['key' => $templateData['key']],
                $payload
            );
        }
    }
}
