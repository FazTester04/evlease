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
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 0. MAINTENANCE: Auto-update any pending payments that passed their due date
        // This ensures Admin and Driver dashboards stay in sync
        LeasePayment::where('status', 'pending')
            ->where('due_date', '<', now()->startOfDay())
            ->update(['status' => 'overdue']);

        // ---------- KPI Cards ----------
        $totalVehiclesLeased = Lease::where('status', 'active')->count();
        
        $monthlyRevenue = LeasePayment::where('status', 'paid')
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->sum('amount');
            
        // Now we just count the status 'overdue' thanks to Step 0
        $overduePaymentsCount = LeasePayment::where('status', 'overdue')->count();

        // ---------- Payment Status Overview (For Charts) ----------
        $paidOnTime = LeasePayment::where('status', 'paid')
            ->whereColumn('paid_date', '<=', 'due_date')
            ->count();
            
        $late = LeasePayment::where('status', 'paid')
            ->whereColumn('paid_date', '>', 'due_date')
            ->count();
            
        $overdue = $overduePaymentsCount;
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

        // ---------- Vehicles Requiring Follow-up (Action List) ----------
        $vehiclesFollowUp = Lease::with(['car', 'driver', 'payments'])
            ->whereHas('payments', function ($q) {
                $q->where('status', 'overdue');
            })
            ->get()
            ->map(function ($lease) {
                // Get the oldest overdue payment
                $overduePayment = $lease->payments
                    ->where('status', 'overdue')
                    ->sortBy('due_date')
                    ->first();
                
                $carName = $lease->car 
                    ? trim($lease->car->make . ' ' . $lease->car->model) 
                    : 'Unknown Vehicle';
                
                return [
                    'vehicle_id' => $lease->car->license_plate ?? 'N/A',
                    'vehicle_name' => $carName,
                    'driver_name' => $lease->driver->name ?? 'Unknown',
                    'amount' => $overduePayment->amount ?? 0,
                    'payment_due' => $overduePayment->due_date ? $overduePayment->due_date->format('d M Y') : '',
                    'last_paid' => $lease->payments
                        ->where('status', 'paid')
                        ->sortByDesc('paid_date')
                        ->first()?->paid_date?->format('d M Y') ?? 'Never',
                    'days_overdue' => $overduePayment ? now()->diffInDays($overduePayment->due_date) : 0,
                ];
            });

        // ---------- All Active Fleet List ----------
        $allLeasedVehicles = Lease::with('car')
            ->where('status', 'active')
            ->get()
            ->map(function ($lease) {
                return [
                    'id' => $lease->car->id ?? null,
                    'license_plate' => $lease->car->license_plate ?? 'N/A',
                    'model' => $lease->car ? trim($lease->car->make . ' ' . $lease->car->model) : 'N/A',
                ];
            });

        return Inertia::render('Dashboard/Index', [
            'kpis' => [
                'totalVehiclesLeased' => $totalVehiclesLeased,
                'monthlyRevenue' => number_format($monthlyRevenue, 2),
                'overduePaymentsCount' => $overduePaymentsCount,
                'vehiclesLeasedCount' => $totalVehiclesLeased,
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