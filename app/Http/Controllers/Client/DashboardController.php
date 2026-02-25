<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Fetch leases belonging to the authenticated user
        $leases = $user->leases()->with('car')->get();

        // Fetch documents for these leases (or for the user)
        $documents = $user->documents()->latest()->take(5)->get();

        // Fetch payment history for these leases
        $payments = $user->payments()->latest()->take(10)->get();

        return Inertia::render('Client/Dashboard', [
            'leases' => $leases,
            'documents' => $documents,
            'payments' => $payments,
        ]);
    }
}