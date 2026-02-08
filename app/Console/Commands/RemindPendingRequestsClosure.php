<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Clocks\Models\UserClockOvertime;
use Modules\Users\Enums\NotificationType;
use Modules\Users\Models\Excuse;
use Modules\Users\Models\SystemNotification;
use Modules\Users\Models\SystemNotificationRecipient;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;

class RemindPendingRequestsClosure extends Command
{
    protected $signature = 'requests:remind-closure {--force}';
    protected $description = 'Send notification to all managers and team leaders to remind them to close pending overtime, vacation, and excuse requests on the 25th of each month.';

    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();

        // Check if today is the 25th of the month (unless --force is used)
        if ($today->day !== 25 && !$this->option('force')) {
            $this->info('Today is not the 25th of the month. No action applied.');
            return Command::SUCCESS;
        }

        $this->info('Sending pending requests closure reminders...');

        // Get all managers and team leaders
        $managersAndTeamLeaders = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Manager', 'Team leader']);
            })
            ->get();

        if ($managersAndTeamLeaders->isEmpty()) {
            $this->warn('No managers or team leaders found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$managersAndTeamLeaders->count()} managers/team leaders.");

        // Count pending requests for the notification message
        $pendingVacations = UserVacation::where('status', 'pending')->count();
        $pendingExcuses = Excuse::where('status', 'pending')->count();
        $pendingOvertimes = UserClockOvertime::where(function ($query) {
            $query->where('approval_of_direct', 'pending')
                ->orWhere('approval_of_head', 'pending');
        })->count();

        $totalPending = $pendingVacations + $pendingExcuses + $pendingOvertimes;

        if ($totalPending === 0) {
            $this->info('No pending requests found. No notification sent.');
            return Command::SUCCESS;
        }

        $this->info("Pending requests found: Vacations: {$pendingVacations}, Excuses: {$pendingExcuses}, Overtimes: {$pendingOvertimes}");

        // Create the notification
        $notification = new SystemNotification([
            'type' => NotificationType::ActionRequired->value,
            'title' => 'Monthly Request Closure Reminder',
            'message' => "This is a reminder to review and close all pending requests before the end of the payroll period." .
                "Please review and take action on all pending requests assigned to you.",
            'scope_type' => 'custom',
            'scope_id' => null,
            'filters' => ['roles' => ['Manager', 'Team leader']],
            'created_by' => null, // System generated
            'scheduled_at' => null,
        ]);

        DB::transaction(function () use ($notification, $managersAndTeamLeaders) {
            $notification->save();

            $rows = $managersAndTeamLeaders->map(function (User $user) use ($notification) {
                $now = Carbon::now();

                return [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'status' => 'sent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SystemNotificationRecipient::query()->insert($rows);
        });

        $this->info("Notification sent successfully to {$managersAndTeamLeaders->count()} recipients.");
        $this->info("Notification ID: {$notification->id}");

        return Command::SUCCESS;
    }
}
