<?php

namespace App\Console\Commands;

use App\Models\LeasePayment;
use App\Models\Document;
use Illuminate\Console\Command;

class MovePaymentProofs extends Command
{
    protected $signature = 'move:payment-proofs';
    protected $description = 'Move payment proofs from lease_payments to documents';

    public function handle()
    {
        $payments = LeasePayment::whereNotNull('proof_path')->get();

        foreach ($payments as $payment) {
            Document::create([
                'lease_payment_id' => $payment->id,
                'name'             => 'Payment Receipt',
                'type'             => 'payment_receipt',
                'file_path'        => $payment->proof_path,
                'expiry_date'      => null,
                'status'           => 'valid',
            ]);
            $this->info("Moved proof for payment #{$payment->id}");
        }

        $this->info('All done!');
    }
}