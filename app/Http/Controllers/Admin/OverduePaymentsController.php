<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeasePayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class OverduePaymentsController extends Controller
{
    public function index(Request $request)
    {
        $payments = LeasePayment::with(['lease.car', 'lease.driver'])
            ->where('status', 'overdue')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('lease.car', function ($q) use ($search) {
                    $q->where('license_plate', 'like', "%{$search}%");
                })->orWhereHas('lease.driver', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return Inertia::render('Overdue/Index', [
            'payments' => $payments,
            'filters' => $request->only('search'),
        ]);
    }
}