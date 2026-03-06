<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateMonthlyPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-monthly-payments';

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
    // Find all active leases
    $leases = Lease::where('status', 'active')->get();

    foreach ($leases as $lease) {
        // Get the most recent payment date for this lease
        $latestPayment = $lease->payments()->latest('due_date')->first();

        // If no payment exists, use the start date. 
        // Otherwise, use the date of the latest record.
        $lastDate = $latestPayment 
            ? Carbon::parse($latestPayment->due_date) 
            : Carbon::parse($lease->start_date)->subMonth();

        // If the last payment we have is coming up soon (e.g., within 45 days),
        // we create the next one so the driver can see it on their dashboard.
        if ($lastDate->diffInDays(now()) < 45) {
            $lease->payments()->create([
                'amount' => $lease->monthly_payment,
                'due_date' => $lastDate->addMonth(),
                'status' => 'pending',
            ]);
            
            $this->info("Generated next payment for Lease #{$lease->id}");
        }
    }
}
