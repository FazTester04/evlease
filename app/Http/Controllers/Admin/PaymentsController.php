<?php

namespace App\Http\Controllers;

use App\Models\LeasePayment;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    /**
     * Record a new payment.
     */
    public function record(Request $request)
    {
        $validated = $request->validate([
            'lease_id'  => 'required|exists:leases,id',
            'amount'    => 'required|numeric|min:0',
            'paid_date' => 'required|date',
            'due_date'  => 'required|date',
        ]);

        LeasePayment::create($validated);

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}