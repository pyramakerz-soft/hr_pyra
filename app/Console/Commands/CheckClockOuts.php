<?php

namespace App\Console\Commands;

use App\Events\CheckClockOutsEvent;
use Illuminate\Console\Command;

class CheckClockOuts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:clock_outs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for missing clock_out entries and trigger event';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fire the event to check for missing clock_out entries
        event(new CheckClockOutsEvent());

        // Output to console
        $this->info('Check ClockOuts Event Fired Successfully.');
    }
}
