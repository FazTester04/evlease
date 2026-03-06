<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Lease;

class GenerateRollingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-rolling-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
public function handle()
{
    // 1. Get all active leases
    $leases = Lease::where('status', 'active')->get();

    foreach ($leases as $lease) {
        // 2. Count existing payments to ensure we don't exceed the contract duration
        $count = $lease->payments()->count();
        if ($count >= $lease->duration_months) continue;

        // 3. Get the latest payment date
        $latestPayment = $lease->payments()->latest('due_date')->first();
        $nextDueDate = Carbon::parse($latestPayment->due_date)->addMonth();

        // 4. Generate the next month if we are within 45 days of the last payment record
        // This ensures the driver always sees "Next Month" on their dashboard
        if (now()->diffInDays(Carbon::parse($latestPayment->due_date)) < 45) {
            $lease->payments()->create([
                'amount' => $lease->monthly_payment,
                'due_date' => $nextDueDate,
                'status' => 'pending',
            ]);
        }
    }
}
}
