<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Users\Models\UserVacationBalance;

class AccrueMonthlyVacationBalances extends Command
{
    protected $signature = 'vacations:accrue-monthly {--date=}';

    protected $description = 'Increase each vacation balance by 1.75 days on the first day of every month.';

    public function handle(): int
    {
        $referenceDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        if ($referenceDate->day !== 1) {
            $this->info('Today is not the first day of the month. No accrual applied.');
            return Command::SUCCESS;
        }

        $year = $referenceDate->year;

        DB::transaction(function () use ($referenceDate, $year) {
            // Ensure every existing user/type pairing has a row for the active year
            $distinctPairs = UserVacationBalance::query()
                ->select('user_id', 'vacation_type_id')
                ->distinct()
                ->get();

            $existingYearPairs = UserVacationBalance::query()
                ->where('year', $year)
                ->get()
                ->mapWithKeys(function (UserVacationBalance $balance) {
                    return [sprintf('%d-%d', $balance->user_id, $balance->vacation_type_id) => true];
                });

            foreach ($distinctPairs as $pair) {
                $key = sprintf('%d-%d', $pair->user_id, $pair->vacation_type_id);
                if (! isset($existingYearPairs[$key])) {
                    UserVacationBalance::create([
                        'user_id' => $pair->user_id,
                        'vacation_type_id' => $pair->vacation_type_id,
                        'year' => $year,
                        'allocated_days' => 0,
                        'used_days' => 0,
                    ]);
                }
            }

            UserVacationBalance::query()
                ->where('year', $year)
                ->chunkById(500, function ($balances) use ($referenceDate) {
                    foreach ($balances as $balance) {
                        $lastAccrued = $balance->last_accrued_at ? Carbon::parse($balance->last_accrued_at) : null;

                        if ($lastAccrued && $lastAccrued->isSameMonth($referenceDate)) {
                            continue;
                        }

                        $balance->allocated_days = ($balance->allocated_days ?? 0) + 1.75;
                        $balance->last_accrued_at = $referenceDate;
                        $balance->save();
                    }
                });
        });

        $this->info('Monthly vacation balances accrued successfully.');

        return Command::SUCCESS;
    }
}
