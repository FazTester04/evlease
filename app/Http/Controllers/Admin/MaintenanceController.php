<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceService;
use App\Models\Car;
use App\Models\Lease;
use App\Enums\LeaseStatus;
use App\Enums\CarStatus;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $maintenances = MaintenanceService::with('car', 'driver')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Maintenance/Index', [
            'maintenances' => $maintenances,
            'filters' => $request->only(['status']),
        ]);
    }

   public function complete(MaintenanceService $maintenance)
{
    $maintenance->update([
        'status' => 'completed',
        'completed_date' => now()->toDateString(),
    ]);

    $car = $maintenance->car;

    // If there was a driver recorded, try to resume their lease
    if ($maintenance->driver_id) {
        $lease = Lease::where('car_id', $car->id)
            ->where('driver_id', $maintenance->driver_id)
            ->where('status', LeaseStatus::PAUSED)
            ->first();

        if ($lease) {
            // Resume the lease
            $lease->update(['status' => LeaseStatus::ACTIVE]);
            $car->update(['status' => CarStatus::LEASED]);
        } else {
            // No paused lease found, just make the car available
            $car->update(['status' => CarStatus::AVAILABLE]);
        }
    } else {
        // No driver recorded (car had no lease) – just make available
        $car->update(['status' => CarStatus::AVAILABLE]);
    }

    return redirect()->back()->with('success', 'Maintenance completed. Car status updated.');
}
}