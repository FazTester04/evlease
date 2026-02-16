<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Lease;
use App\Models\LeasePayment;
use App\Models\MaintenanceService;
use App\Models\Document;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // ---------- KPI Cards ----------
        $totalVehiclesLeased = Lease::where('status', 'active')->count();
        
        $monthlyRevenue = LeasePayment::where('status', 'paid')
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->sum('amount');
            
        $overduePaymentsCount = LeasePayment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();
            
        $vehiclesLeasedCount = $totalVehiclesLeased;

        // ---------- Payment Status Overview ----------
        $paidOnTime = LeasePayment::where('status', 'paid')
            ->whereColumn('paid_date', '<=', 'due_date')
            ->count();
            
        $late = LeasePayment::where('status', 'paid')
            ->whereColumn('paid_date', '>', 'due_date')
            ->count();
            
        $overdue = LeasePayment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        $totalLeases = Lease::count();

        // ---------- Maintenance ----------
        $vehiclesInMaintenance = Car::where('status', 'maintenance')->count();
        
        $overdueServices = MaintenanceService::where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'scheduled')
                  ->where('scheduled_date', '<', now());
            })->count();

        // ---------- Documents ----------
        $expiringDocuments = Document::where('expiry_date', '>', now())
            ->where('expiry_date', '<', now()->addDays(30))
            ->count();

        // ---------- Vehicles Requiring Payment Follow-up ----------
        $vehiclesFollowUp = Lease::with(['car', 'driver', 'payments' => function ($q) {
                $q->where('status', 'pending')->where('due_date', '<', now());
            }])
            ->whereHas('payments', function ($q) {
                $q->where('status', 'pending')->where('due_date', '<', now());
            })
            ->get()
            ->map(function ($lease) {
                $overduePayment = $lease->payments->first();
                
                // Format car name from make + model
                $carName = $lease->car 
                    ? trim($lease->car->make . ' ' . $lease->car->model) 
                    : 'Unknown Vehicle';
                
                return [
                    'vehicle_id' => $lease->car->license_plate ?? 'N/A',
                    'vehicle_name' => $carName,
                    'driver_name' => $lease->driver->name ?? 'Unknown',
                    'driver_id' => $lease->driver->driver_license ?? $lease->driver->id ?? null,
                    'amount' => $overduePayment->amount ?? 0,
                    'payment_due' => $overduePayment->due_date ? $overduePayment->due_date->format('n/j/Y') : '',
                    'last_paid' => $lease->payments()
                        ->where('status', 'paid')
                        ->latest('paid_date')
                        ->first()?->paid_date?->format('n/j/Y') ?? 'Never',
                    'days_overdue' => $overduePayment ? now()->diffInDays($overduePayment->due_date) : 0,
                ];
            });

        // ---------- All Leased Vehicles ----------
        $allLeasedVehicles = Lease::with('car')
            ->where('status', 'active')
            ->get()
            ->map(function ($lease) {
                return [
                    'id' => $lease->car->id,
                    'license_plate' => $lease->car->license_plate,
                    'model' => trim($lease->car->make . ' ' . $lease->car->model),
                ];
            });

        return Inertia::render('Dashboard/Index', [
            'kpis' => [
                'totalVehiclesLeased' => $totalVehiclesLeased,
                'monthlyRevenue' => number_format($monthlyRevenue, 2),
                'overduePaymentsCount' => $overduePaymentsCount,
                'vehiclesLeasedCount' => $vehiclesLeasedCount,
            ],
            'paymentStatus' => [
                'paidOnTime' => $paidOnTime,
                'late' => $late,
                'overdue' => $overdue,
                'totalLeases' => $totalLeases,
            ],
            'maintenance' => [
                'vehiclesInMaintenance' => $vehiclesInMaintenance,
                'overdueServices' => $overdueServices,
            ],
            'expiringDocuments' => $expiringDocuments,
            'vehiclesFollowUp' => $vehiclesFollowUp,
            'allLeasedVehicles' => $allLeasedVehicles,
        ]);
    }
}