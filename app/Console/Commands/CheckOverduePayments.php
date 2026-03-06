<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeasePayment;
use App\Models\LateFee;
use App\Models\Setting;
use Carbon\Carbon;

class CheckOverduePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark pending payments as overdue and apply late fees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // 1. Mark pending payments as overdue if due date has passed
        $updated = LeasePayment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->update(['status' => 'overdue']);

        $this->info("Marked {$updated} payments as overdue.");

        // 2. Get global late fee settings
        $lateFeeRate = Setting::where('key', 'late_fee_rate')->value('value') ?? 2.00;
        $lateFeeCap  = Setting::where('key', 'late_fee_cap')->value('value') ?? 50.00;

        // 3. Get all overdue payments that don't already have a paid late fee
        $overduePayments = LeasePayment::with('lease')
            ->where('status', 'overdue')
            ->whereDoesntHave('lateFees', function ($query) {
                $query->where('status', 'paid');
            })
            ->get();

        $this->info("Processing late fees for " . $overduePayments->count() . " overdue payments.");

        foreach ($overduePayments as $payment) {
            // Calculate months overdue (full months past due)
            $monthsOverdue = $payment->due_date->diffInMonths($today) + 1;

            // Base late fee = payment amount * (late_fee_rate% * months)
            $feeAmount = $payment->amount * ($lateFeeRate / 100) * $monthsOverdue;

            // Apply cap (maximum percentage of payment)
            $maxFee = $payment->amount * ($lateFeeCap / 100);
            $feeAmount = min($feeAmount, $maxFee);

            // Round to 2 decimals
            $feeAmount = round($feeAmount, 2);

            // Check if a late fee for this payment and month already exists
            $existing = LateFee::where('lease_payment_id', $payment->id)
                ->where('months_overdue', $monthsOverdue)
                ->first();

            if (!$existing && $feeAmount > 0) {
                LateFee::create([
                    'lease_payment_id' => $payment->id,
                    'lease_id'         => $payment->lease_id,
                    'amount'           => $feeAmount,
                    'months_overdue'   => $monthsOverdue,
                    'status'           => 'pending',
                ]);
                $this->line("Late fee added: Payment {$payment->id} – {$feeAmount}");
            }
        }

        $this->info('Late fee processing completed.');
    }
}