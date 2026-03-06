<?php

namespace App\Console\Commands;

use App\Models\Lease;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOverdueLeases extends Command
{
    protected $signature = 'leases:check-overdue';
    protected $description = 'Mark leases as overdue if no payment received for the current month';

    public function handle()
    {
        $leases = Lease::where('status', 'active')->get();

        foreach ($leases as $lease) {
            $lastPayment = $lease->payments()->latest('due_date')->first();

            if (!$lastPayment) {
                // No payments at all – overdue if start date is in the past
                $isOverdue = Carbon::parse($lease->start_date)->lt(now());
            } else {
                // Next due date is the last due date + 1 month
                $nextDueDate = Carbon::parse($lastPayment->due_date)->addMonth();
                // Check if today is past the next due date and no payment exists for that period
                $paymentExists = $lease->payments()
                    ->whereDate('due_date', $nextDueDate->toDateString())
                    ->exists();

                $isOverdue = now()->gt($nextDueDate) && !$paymentExists;
            }

            if ($lease->overdue != $isOverdue) {
                $lease->update(['overdue' => $isOverdue]);
                $this->info("Lease ID {$lease->id} overdue set to ".($isOverdue ? 'true' : 'false'));
            }
        }

        $this->info('Overdue check completed.');
    }
}