<?php

namespace Modules\Clocks\Support;

use Carbon\Carbon;

class OvertimeCalculator
{
    public static function calculate(int $dailyWorkedMinutes, ?string $date = null): int
    {
        if ($date) {
            $d = Carbon::parse($date);
            if ($d->isFriday() || $d->isSaturday()) {
                return $dailyWorkedMinutes;
            }
        }

        if ($dailyWorkedMinutes < 535) {
            return 0;
        }

        $extraAfterNine = $dailyWorkedMinutes - 535;
        $blocks = intdiv($extraAfterNine, 15);

        return 60 + ($blocks * 15);
    }
}
