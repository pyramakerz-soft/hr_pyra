<?php

namespace Modules\Clocks\Support;

class ServiceActionRegistry
{
    public const ACTION_FORCE_CLOCK_OUT = 'force_clock_out';
    public const ACTION_RESOLVE_CLOCK_ISSUES = 'resolve_clock_issues';
    public const ACTION_RECOMPUTE_DURATIONS = 'recompute_durations';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => self::ACTION_FORCE_CLOCK_OUT,
                'label' => 'Force Clock Out',
                'description' => 'Close open shifts by automatically setting a clock-out time for the selected scope.',
                'payload_fields' => [
                    [
                        'key' => 'date',
                        'label' => 'Target Date',
                        'type' => 'date',
                        'required' => false,
                    ],
                    [
                        'key' => 'clock_out_time',
                        'label' => 'Clock-out Time (HH:MM)',
                        'type' => 'time',
                        'required' => false,
                    ],
                    [
                        'key' => 'default_duration_minutes',
                        'label' => 'Fallback Minutes',
                        'type' => 'number',
                        'required' => false,
                    ],
                ],
            ],
            [
                'key' => self::ACTION_RESOLVE_CLOCK_ISSUES,
                'label' => 'Resolve Clock Issues',
                'description' => 'Mark outstanding clock issues as resolved within the selected window.',
                'payload_fields' => [
                    [
                        'key' => 'from_date',
                        'label' => 'From Date',
                        'type' => 'date',
                        'required' => false,
                    ],
                    [
                        'key' => 'to_date',
                        'label' => 'To Date',
                        'type' => 'date',
                        'required' => false,
                    ],
                ],
            ],
            [
                'key' => self::ACTION_RECOMPUTE_DURATIONS,
                'label' => 'Recompute Durations',
                'description' => 'Recalculate working durations for completed shifts to ensure accuracy.',
                'payload_fields' => [
                    [
                        'key' => 'from_date',
                        'label' => 'From Date',
                        'type' => 'date',
                        'required' => false,
                    ],
                    [
                        'key' => 'to_date',
                        'label' => 'To Date',
                        'type' => 'date',
                        'required' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    /**
     * @param string $key
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $action) {
            if ($action['key'] === $key) {
                return $action;
            }
        }

        return null;
    }
}

