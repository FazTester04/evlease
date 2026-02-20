<?php

namespace App\Http\Controllers;

use App\Models\LeasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentsController extends Controller
{
    /**
     * Record a new payment with optional proof.
     */
    public function record(Request $request)
    {
        $validated = $request->validate([
            'lease_id'  => 'required|exists:leases,id',
            'amount'    => 'required|numeric|min:0',
            'paid_date' => 'required|date',
            'due_date'  => 'required|date',
            'proof'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        $data = [
            'lease_id'  => $validated['lease_id'],
            'amount'    => $validated['amount'],
            'paid_date' => $validated['paid_date'],
            'due_date'  => $validated['due_date'],
            'status'    => 'paid', // or 'pending_verification' if needed
        ];

        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('payment-proofs', 'public');
            $data['proof_path'] = $path;
        }

        LeasePayment::create($data);

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    /**
     * Delete a payment record.
     */
    public function destroy(LeasePayment $payment)
    {
        // Optionally delete the proof file from storage
        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->delete();

        return redirect()->back()->with('success', 'Payment deleted successfully.');
    }
}                                                                                                                                   