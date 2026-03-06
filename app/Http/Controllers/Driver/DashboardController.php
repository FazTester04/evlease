<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Lease;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Security Check
        if ($user->role !== UserRole::DRIVER) {
            abort(403, 'Unauthorized access.');
        }

        // 2. Fetch Active AND Paused Leases
        $leases = $user->leases()
            ->with('car')
            ->whereIn('status', ['active', 'paused'])
            ->get();

        $totalOutstanding = 0;

        $leases->transform(function ($lease) use (&$totalOutstanding) {
            // Fetch all payments for this lease
            $allPayments = $lease->payments()->get();

            // --- MAINTENANCE CHECK ---
            $lease->in_maintenance = $lease->car && $lease->car->status === 'maintenance';

            // --- TOTAL CONTRACT VALUE (like admin) ---
            $start = Carbon::parse($lease->start_date);
            $end = $lease->end_date ? Carbon::parse($lease->end_date) : Carbon::now();
            $months = $start->diffInMonths($end) + 1; // inclusive
            $downPayment = $lease->down_payment ?? 0;
            $totalContractValue = ($lease->monthly_payment * $months) + $downPayment;

            $totalPaid = $allPayments->where('status', 'paid')->sum('amount');

            $lease->outstanding_balance = $totalContractValue - $totalPaid;
            $totalOutstanding += $lease->outstanding_balance;

            // --- NEXT DUE DATE LOGIC ---
            $nextUnpaidPayment = $allPayments
                ->whereIn('status', ['pending', 'overdue'])
                ->sortBy('due_date')
                ->first();

            $lease->next_due_date = $nextUnpaidPayment ? $nextUnpaidPayment->due_date : null;

            // --- RECENT ACTIVITY FEED ---
            $lease->recent_payments = $allPayments->sortByDesc('due_date')
                ->take(5)
                ->values();

            return $lease;
        });

        return Inertia::render('Driver/Dashboard', [
            'leases' => $leases,
            'totalOutstanding' => $totalOutstanding,
        ]);
    }
}