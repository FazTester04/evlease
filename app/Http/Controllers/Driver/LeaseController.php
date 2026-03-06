<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Lease;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // <-- add this import

class LeaseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $leases = $user->leases()
            ->with('car')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Driver/Leases/Index', [
            'leases' => $leases,
        ]);
    }

    public function show(Lease $lease)
    {
        $user = Auth::user();

        if ($lease->driver_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $lease->load('car', 'driver');
        $payments = $lease->payments()->orderBy('due_date')->get();

        // --- Calculate total contract value using lease dates and down payment ---
        $start = Carbon::parse($lease->start_date);
        $end = $lease->end_date ? Carbon::parse($lease->end_date) : Carbon::now();
        $months = $start->diffInMonths($end) + 1; // inclusive
        $downPayment = $lease->down_payment ?? 0;
        $totalContractValue = ($lease->monthly_payment * $months) + $downPayment;

        // Total paid
        $totalPaid = $payments->where('status', 'paid')->sum('amount');

        // Outstanding = contract value - paid
        $outstanding = $totalContractValue - $totalPaid;

        // Next payment
        $nextPayment = $payments->whereIn('status', ['pending', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return Inertia::render('Driver/Leases/Show', [
            'lease' => $lease,
            'payments' => $payments,
            'total_paid' => $totalPaid,
            'total_pending' => $outstanding, // now matches dashboard's outstanding_balance
            'next_payment' => $nextPayment,
        ]);
    }
}