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
        'proof'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'remarks'   => 'nullable|string|max:1000', // <-- new
    ]);

    $data = [
        'lease_id'  => $validated['lease_id'],
        'amount'    => $validated['amount'],
        'paid_date' => $validated['paid_date'],
        'due_date'  => $validated['due_date'],
        'status'    => 'paid',
        'remarks'   => $validated['remarks'] ?? null, // <-- store
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

    /**
     * List all payments (for admin).
     */
    public function index(Request $request)
    {
        $payments = LeasePayment::with('lease.car', 'lease.driver', 'document')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('lease.car', function ($q) use ($search) {
                    $q->where('license_plate', 'like', "%{$search}%");
                })->orWhereHas('lease.driver', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('paid_date', 'desc')
            ->get();

        return Inertia::render('FleetPayments', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Generate a detailed statement for a lease.
     */
    public function statement(Lease $lease)
    {
        // Load necessary relationships (including lateFees)
        $lease->load('car', 'driver', 'lateFees');

        // Calculate lease duration in months
        $start = Carbon::parse($lease->start_date);
        $end = $lease->end_date ? Carbon::parse($lease->end_date) : Carbon::now();
        $months = max(1, $start->diffInMonths($end)); // at least 1 month

        // (a) Total Lease Value = monthly_payment * months (down payment separate)
        $totalLeaseValue = $months * $lease->monthly_payment;

        // (d) Late charges
        $additionalLateCharges = $lease->lateFees->sum('amount');

        // 🔁 Direct query to get payments (bypass relationship issues)
        $payments = LeasePayment::where('lease_id', $lease->id)->orderBy('due_date')->get();

        // (e) Payments Made
        $paymentsMade = $payments->sum('amount');

        // (f) Total Payable = a + d - e
        $totalPayable = $totalLeaseValue + $additionalLateCharges - $paymentsMade;

        // Add timeliness to each payment (optional)
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
            'lease' => $lease,
            'payments' => $payments,
            'late_fees' => $lease->lateFees,
            'total_lease_value' => $totalLeaseValue,
            'monthly_installment' => $lease->monthly_payment,
            'initial_deposit' => $lease->down_payment,
            'additional_late_charges' => $additionalLateCharges,
            'payments_made' => $paymentsMade,          // <-- ADDED
            'total_payable' => $totalPayable,          // <-- ADDED
            // Keep the old fields for compatibility (optional)
            'down_payment' => $lease->down_payment,
            'monthly_payment' => $lease->monthly_payment,
            'outstanding' => $totalPayable,             // or keep as old calculation if needed
        ]);
    }

    public function approve(LeasePayment $payment)
    {
        // Only allow approving pending payments
        if ($payment->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payments can be approved.');
        }

        $payment->update([
            'status' => 'paid',
            'paid_date' => Carbon::now()->toDateString(),
        ]);

        // Optionally, mark the related document as verified
        if ($payment->document) {
            $payment->document->update(['verified_at' => Carbon::now()]);
        }

        return redirect()->back()->with('success', 'Payment approved successfully.');
    }
}
