<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LeasePayment;
use Illuminate\Http\Request;
use App\Models\Lease;
use Carbon\Carbon;
use App\Models\Document;
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

     $payment = LeasePayment::create($data);

if ($request->hasFile('proof')) {
    $path = $request->file('proof')->store('payment-proofs', 'public');
    Document::create([
        'lease_payment_id' => $payment->id,
        'name'             => 'Payment Receipt',
        'type'             => 'payment_receipt',
        'file_path'        => $path,
        'expiry_date'      => null,
        'status'           => 'valid',
    ]);
}

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    /**
     * Delete a payment record.
     */
public function destroy(LeasePayment $payment)
{
    if ($payment->document) {
        Storage::disk('public')->delete($payment->document->file_path);
        $payment->document->delete();
    }
    $payment->delete();
    return redirect()->back()->with('success', 'Payment deleted successfully.');
}
public function index()
{
    $payments = LeasePayment::with('lease.car', 'lease.driver')
     ->orderBy('paid_date', 'desc')->get();

    return Inertia::render('FleetPayments', [
        'payments' => $payments,
    ]);
}
public function statement(Lease $lease)
{
    // Ensure the authenticated user is admin (add policy if needed)
  //  $this->authorize('view', $lease); // optional

    $payments = $lease->payments()->orderBy('paid_date')->get();

    // Calculate total paid
    $totalPaid = $payments->sum('amount');

    // Calculate total due based on lease duration up to now or end date
    $start = Carbon::parse($lease->start_date);
    $end = $lease->end_date ? Carbon::parse($lease->end_date) : Carbon::now();
    $months = $start->diffInMonths($end) + 1; // including start month
    $totalDue = $lease->monthly_payment * $months;

    // Add timeliness to each payment
    $payments = $payments->map(function ($p) {
        $dueDate = Carbon::parse($p->due_date);
        $paidDate = Carbon::parse($p->paid_date);
        if ($paidDate->lt($dueDate)) {
            $p->timeliness = 'early';
        } elseif ($paidDate->eq($dueDate)) {
            $p->timeliness = 'on-time';
        } else {
            $p->timeliness = 'late';
        }
        return $p;
    });

    return response()->json([
        'lease' => $lease->load('car', 'driver'),
        'payments' => $payments,
        'total_paid' => $totalPaid,
        'total_due' => $totalDue,
        'outstanding' => $totalDue - $totalPaid,
    ]);
}

}                                                                                                                                   