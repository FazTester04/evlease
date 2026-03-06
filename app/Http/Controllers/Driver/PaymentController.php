<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Lease;
use App\Models\LeasePayment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a list of the driver's payments.
     */
    public function index()
    {
        $user = Auth::user();

        $payments = $user->payments()
            ->with('lease.car')
            ->orderBy('due_date', 'desc')
            ->paginate(10);

        return Inertia::render('Driver/Payments/Index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Show the form to make a new payment.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get active leases for the driver
        $leases = $user->leases()
            ->where('status', 'active')
            ->with('car')
            ->get();

        // If a lease_id is provided, pre-select it
        $selectedLease = null;
        if ($request->lease_id) {
            $selectedLease = $leases->find($request->lease_id);
        }

        return Inertia::render('Driver/Payments/Create', [
            'leases' => $leases,
            'selectedLease' => $selectedLease,
        ]);
    }

    /**
     * Store a new payment record (simulate payment initiation).
     */
public function store(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'lease_id' => 'required|exists:leases,id',
        'amount' => 'required|numeric|min:0.01',
        'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
    ]);

    // Ensure the lease belongs to the driver
    $lease = Lease::findOrFail($request->lease_id);
    if ($lease->driver_id !== $user->id) {
        abort(403, 'Unauthorized.');
    }

    // Store receipt file
    $path = $request->file('receipt')->store('payment-receipts/' . $user->id, 'public');

    // Create payment record
    $payment = $lease->payments()->create([
        'driver_id' => $user->id,
        'amount' => $request->amount,
        'due_date' => now(), // replace with actual logic if needed
        'status' => 'pending',
        'receipt_path' => $path,
    ]);

    return redirect()->route('driver.payments.index')
        ->with('success', 'Payment receipt uploaded successfully. It will be verified shortly.');
}
}