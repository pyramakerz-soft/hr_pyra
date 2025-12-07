<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;
use Modules\Users\Models\User;

class AccrueMonthlyVacationBalances extends Command
{
    //TODO change the balance increase rate here to 1.25 day per month for the first year after the user is created DONE
    protected $signature = 'vacations:accrue-monthly {--date=} {--force}';

    protected $description = 'Increase each vacation balance by 1.75 days on the first day of every month.';

    public function handle(): int
    {
        $referenceDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        if ($referenceDate->day !== 1 && !$this->option('force')) {
            $this->info('Today is not the first day of the month. No accrual applied.');
            return Command::SUCCESS;
        }

        $year = $referenceDate->year;
        $userIds = User::pluck('id');

        DB::transaction(function () use ($referenceDate, $year, $userIds) {

            $annualLeaveVacationType = VacationType::where('name', 'Annual Leave')->first();
            // Ensure every existing user/type pairing has a row for the active year
            // Ensure every user has a row for the active year

            $existingYearPairs = UserVacationBalance::query()
                ->where('year', $year)
                ->where('vacation_type_id', $annualLeaveVacationType->id)
                ->get()
                ->mapWithKeys(function (UserVacationBalance $balance) use ($annualLeaveVacationType) {
                    return [sprintf('%d-%d', $balance->user_id, $annualLeaveVacationType->id) => true];
                });

            foreach ($userIds as $userId) {
                $key = sprintf('%d-%d', $userId, $annualLeaveVacationType->id);
                if (!isset($existingYearPairs[$key])) {
                    UserVacationBalance::create([
                        'user_id' => $userId,
                        'vacation_type_id' => $annualLeaveVacationType->id,
                        'year' => $year,
                        'allocated_days' => 0,
                        'used_days' => 0,
                    ]);
                }
            }

            UserVacationBalance::query()
                ->where('year', $year)
                ->with('user.user_detail') // Eager load user and their details
                ->chunkById(500, function ($balances) use ($referenceDate) {
                    foreach ($balances as $balance) {
                        $lastAccrued = $balance->last_accrued_at ? Carbon::parse($balance->last_accrued_at) : null;

                        if ($lastAccrued && $lastAccrued->isSameMonth($referenceDate)) {
                            continue;
                        }

                        $accrualRate = 1.75; // Default rate for users > 1 year
                        $user = $balance->user;

                        $userDetail = $user->user_detail;
                        if ($userDetail && $userDetail->hiring_date) {
                            $hiringDate = Carbon::parse($userDetail->hiring_date);
                            $ruleStartDate = Carbon::create(2025, 9, 1)->startOfDay();
                            $oneYearAfterHiring = $hiringDate->copy()->addYear();

                            if ($hiringDate->gte($ruleStartDate) && $referenceDate->lessThan($oneYearAfterHiring)) {
                                $accrualRate = 1.25; // Rate for employees hired from September 2025 onward in their first year
                            }
                        }

                        $balance->allocated_days = ($balance->allocated_days ?? 0) + $accrualRate;
                        $balance->last_accrued_at = $referenceDate;
                        $balance->save();
                    }
                });
        });

        // Handle Casual Leave Adjustment
        $casualLeaveType = VacationType::where('name', 'Casual Leave')->first();
        if ($casualLeaveType) {
            // Ensure balances exist for Casual Leave (similar to Annual Leave)
            $existingCasualYearPairs = UserVacationBalance::query()
                ->where('year', $year)
                ->where('vacation_type_id', $casualLeaveType->id)
                ->get()
                ->mapWithKeys(function (UserVacationBalance $balance) use ($casualLeaveType) {
                    return [sprintf('%d-%d', $balance->user_id, $casualLeaveType->id) => true];
                });

            foreach ($userIds as $userId) {
                $key = sprintf('%d-%d', $userId, $casualLeaveType->id);
                if (!isset($existingCasualYearPairs[$key])) {
                    UserVacationBalance::create([
                        'user_id' => $userId,
                        'vacation_type_id' => $casualLeaveType->id,
                        'year' => $year,
                        'allocated_days' => $casualLeaveType->default_days ?? 14,
                        'used_days' => 0,
                    ]);
                }
            }

            UserVacationBalance::query()
                ->where('vacation_type_id', $casualLeaveType->id)
                ->where('year', $year)
                ->with('user.user_detail')
                ->chunkById(500, function ($balances) use ($referenceDate, $casualLeaveType) {
                    foreach ($balances as $balance) {
                        $user = $balance->user;
                        $userDetail = $user->user_detail;
                        $allocatedDays = $casualLeaveType->default_days ?? 14;

                        if ($userDetail && $userDetail->hiring_date) {
                            $hiringDate = Carbon::parse($userDetail->hiring_date);
                            $ruleStartDate = Carbon::create(2025, 9, 1)->startOfDay();
                            $oneYearAfterHiring = $hiringDate->copy()->addYear();

                            if ($hiringDate->gte($ruleStartDate) && $referenceDate->lessThan($oneYearAfterHiring)) {
                                $allocatedDays = 8;
                            }
                        }

                        if ($balance->allocated_days != $allocatedDays) {
                            $balance->allocated_days = $allocatedDays;
                            $balance->save();
                        }
                    }
                });
        }

        // Handle Balance Reset for Academic Department at end of August
        if ($referenceDate->month === 8 && $referenceDate->day === 31) {
            $this->resetAcademicDepartmentBalances($year);
        }

        $this->info('Monthly vacation balances accrued successfully.');

        return Command::SUCCESS;
    }

    protected function resetAcademicDepartmentBalances(int $year): void
    {
        $this->info('Resetting balances for Academic department...');

        // Find Academic department
        $academicDepartment = \Modules\Users\Models\Department::where('name', 'LIKE', '%Academic%')
            ->orWhere('name', 'LIKE', '%academic%')
            ->first();

        if (!$academicDepartment) {
            $this->warn('Academic department not found. Skipping balance reset.');
            return;
        }

        // Get all users in Academic department (including sub-departments)
        $userIds = $academicDepartment->employees()->pluck('id');

        if ($userIds->isEmpty()) {
            $this->info('No employees found in Academic department.');
            return;
        }

        // Reset Annual Leave balances for Academic department employees
        $annualLeaveType = VacationType::where('name', 'Annual Leave')->first();
        if ($annualLeaveType) {
            UserVacationBalance::where('vacation_type_id', $annualLeaveType->id)
                ->where('year', $year)
                ->whereIn('user_id', $userIds)
                ->update([
                    'allocated_days' => 0,
                    'used_days' => 0,
                ]);

            $this->info('Reset Annual Leave balances for ' . $userIds->count() . ' Academic department employees.');
        }

        // Reset Casual Leave balances for Academic department employees
        $casualLeaveType = VacationType::where('name', 'Casual Leave')->first();
        if ($casualLeaveType) {
            UserVacationBalance::where('vacation_type_id', $casualLeaveType->id)
                ->where('year', $year)
                ->whereIn('user_id', $userIds)
                ->update([
                    'allocated_days' => $casualLeaveType->default_days ?? 14,
                    'used_days' => 0,
                ]);

            $this->info('Reset Casual Leave balances for ' . $userIds->count() . ' Academic department employees.');
        }

        $emergencyLeaveType = VacationType::where('name', 'Emergency Leave')->first();
        if ($emergencyLeaveType) {
            UserVacationBalance::where('vacation_type_id', $emergencyLeaveType->id)
                ->where('year', $year)
                ->whereIn('user_id', $userIds)
                ->update([
                    'allocated_days' => $emergencyLeaveType->default_days ?? 14,
                    'used_days' => 0,
                ]);

            $this->info('Reset Emergency Leave balances for ' . $userIds->count() . ' Academic department employees.');
        }
    }
}
