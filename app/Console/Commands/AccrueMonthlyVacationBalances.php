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
    // ... (Existing properties and signature)
    protected $signature = 'vacations:accrue-monthly {--date=} {--force}';
    protected $description = 'Increase each vacation balance by 1.75 days on the first day of every month, and handle year-end carryover.';

    public function handle(): int
    {
        $referenceDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        $isFirstOfMonth = $referenceDate->day === 1;
        $isYearEnd = $referenceDate->month === 12 && $referenceDate->day === 31;

        if (!$isFirstOfMonth && !$isYearEnd && !$this->option('force')) {
            $this->info('Today is not the first day of the month or year-end. No action applied.');
            return Command::SUCCESS;
        }

        $year = $referenceDate->year;
        $userIds = User::pluck('id');

        // --- Core Monthly Accrual Logic (Only runs on the 1st of the month) ---
        if ($isFirstOfMonth || $this->option('force')) {
            $this->info('Running monthly accrual and setup logic...');
            DB::transaction(function () use ($referenceDate, $year, $userIds) {
                // ... (Your existing monthly accrual and setup logic goes here)
                // The existing code block handles Annual Leave accrual and Casual Leave setup.
                // It remains UNCHANGED from what you provided for monthly processing.

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

            // Handle Casual Leave Adjustment (outside of the first transaction, but still part of the 1st of month logic)
            $casualLeaveType = VacationType::where('name', 'Casual Leave')->first();
            if ($casualLeaveType) {
                // ... (Your existing Casual Leave logic remains UNCHANGED here)
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

            $this->info('Monthly vacation balances accrued successfully.');
        }


        // --- Year-End Carryover Logic (Runs only on Dec 31st) ---
        if ($isYearEnd || $this->option('force')) {
            $this->handleYearEndCarryover($year, $userIds);
        }


        // --- Handle Balance Reset for Academic Department at end of August (Existing Logic) ---
        if ($referenceDate->month === 8 && $referenceDate->day === 31) {
            $this->resetB2BDepartmentBalances($year);
        }

        return Command::SUCCESS;
    }

    /**
     * Handles the carryover of remaining vacation days from the current year to the next year.
     */
    protected function handleYearEndCarryover(int $currentYear, $userIds): void
    {
        $this->info("--- Starting Year-End ($currentYear) Carryover Process ---");

        $nextYear = $currentYear + 1;


        $vacationTypes = VacationType::all();
        foreach( $vacationTypes as $vacationType ) {
            
        
            DB::transaction(function () use ($currentYear, $nextYear, $vacationType, $userIds) {
                // 1. Get the final balance (allocated - used) for each user for the current year.
                $carryoverBalances = UserVacationBalance::query()
                    ->where('year', $currentYear)
                    ->where('vacation_type_id', $vacationType->id)
                    ->get()
                    ->mapWithKeys(function (UserVacationBalance $balance) {
                        // Calculate the remaining days
                        $remainingDays = $balance->allocated_days - $balance->used_days;
                        // Carry over the remaining days (you can add a cap here, e.g., max(0, min(5, $remainingDays)))
                        return [$balance->user_id => max(0, $remainingDays)];
                    })
                    ->filter(fn($balance) => $balance > 0); // Only process users with a positive balance

                if ($carryoverBalances->isEmpty()) {
                    $this->info('No remaining ' . $vacationType->name . ' days to carry over');
                    return;
                }

                // 2. Prepare the balances for the next year.
                $nextYearBalances = UserVacationBalance::query()
                    ->where('year', $nextYear)
                    ->where('vacation_type_id', $vacationType->id)
                    ->whereIn('user_id', $carryoverBalances->keys())
                    ->get()
                    ->keyBy('user_id');

                $updates = [];
                $creations = [];

                // 3. Process carryover for each user.
                foreach ($carryoverBalances as $userId => $carryoverDays) {
                    if (isset($nextYearBalances[$userId])) {
                        // User already has a balance row for the next year (shouldn't happen with your accrual logic, but safe to check)
                        $updates[] = [
                            'user_id' => $userId,
                            'vacation_type_id' => $vacationType->id,
                            'year' => $nextYear,
                            // Add the carryover days to the existing allocated days
                            'allocated_days' => $nextYearBalances[$userId]->allocated_days + $carryoverDays,
                        ];
                    } else {
                        // User does NOT have a balance row for the next year, so create a new one.
                        $creations[] = [
                            'user_id' => $userId,
                            'vacation_type_id' => $vacationType->id,
                            'year' => $nextYear,
                            'allocated_days' => $carryoverDays, // Initial allocation is just the carryover
                            'used_days' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Perform batch updates/inserts
                if (!empty($updates)) {
                    $this->warn('Existing Next Year Balance Found - Logic review needed. Using batch update for safety.');
                    // Note: For simplicity, a direct model update is used here, but for many records, a raw query is faster.
                    foreach ($updates as $data) {
                        UserVacationBalance::where('user_id', $data['user_id'])
                            ->where('year', $data['year'])
                            ->where('vacation_type_id', $data['vacation_type_id'])
                            ->update(['allocated_days' => $data['allocated_days']]);
                    }
                }

                if (!empty($creations)) {
                    UserVacationBalance::insert($creations);
                    $this->info('Created ' . count($creations) . ' new ' . $vacationType->name . ' balances for ' . $nextYear . ' with carryover.');
                }

                // 4. Reset the allocated_days of the *current year* to zero after carryover (optional, based on policy)
                // This prevents the user from using the *previous year's* balance in the *new year*,
                // but keeps the record of what was used in the current year.
                UserVacationBalance::where('year', $currentYear)
                    ->where('vacation_type_id', $vacationType->id)
                    ->whereIn('user_id', $carryoverBalances->keys())
                    ->update(['allocated_days' => 0]);

                $this->info($vacationType->name . ' balances for ' . $currentYear . ' have been zeroed out after carryover.');
            });
        }

    $this->info('--- Year-End Carryover Process Completed ---');
    }

    // ... (Your existing resetB2BDepartmentBalances function remains UNCHANGED)
    protected function resetB2BDepartmentBalances(int $year): void
    {
        // ... (Existing implementation of B2B reset)
        $this->info('Resetting balances for B2B department...');

        // Find B2B department
        $b2bDepartment = \Modules\Users\Models\Department::where('name', 'LIKE', '%B2B%')
            ->orWhere('name', 'LIKE', '%b2b%')
            ->first();

        if (!$b2bDepartment) {
            $this->warn('B2B department not found. Skipping balance reset.');
            return;
        }

        // Get all users in B2B department (including sub-departments)
        $userIds = $b2bDepartment->employees()->pluck('id');

        if ($userIds->isEmpty()) {
            $this->info('No employees found in B2B department.');
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

            $this->info('Reset Emergency Leave balances for ' . $userIds->count() . ' B2B department employees.');
        }
    }
}