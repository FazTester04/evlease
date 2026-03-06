<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\Lease;
use App\Models\Driver;
use App\Models\Maintenance;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Enums\LeaseStatus;
use App\Models\MaintenanceService;

class FleetController extends Controller
{
    /**
     * Show the vehicles page (replaces old multi‑tab view)
     */
public function vehicles(Request $request)
{
    $cars = Car::with(['currentDriver', 'roadTax', 'insurance'])
        ->when($request->search, function ($query, $search) {
            $query->where('license_plate', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
        })
        ->when($request->status, function ($query, $status) {  // <-- add this
            $query->where('status', $status);
        })
        ->get();

    $leases = Lease::with('car', 'driver')->get();

    return Inertia::render('Fleet/Index', [
        'cars' => $cars,
        'leases' => $leases,
        'filters' => $request->only(['search', 'status']),
    ]);
}

    /**
     * Show the drivers page (placeholder or real data)
     */
    public function drivers()
    {
        $drivers = Driver::all(); // Adjust as needed

        return Inertia::render('drivers', [
            'drivers' => $drivers,
        ]);
    }
public function index(Request $request)
{
    return $this->vehicles($request);
}
// Add this missing method:
// FleetController.php
public function setMaintenance(Car $vehicle)
{
    // Only proceed if not already in maintenance
    if ($vehicle->status !== CarStatus::MAINTENANCE) {
        // Look for an active OR paused lease (so we can record the driver)
        $lease = Lease::where('car_id', $vehicle->id)
            ->whereIn('status', [LeaseStatus::ACTIVE, LeaseStatus::PAUSED])
            ->first();

        if ($lease) {
            // If it's active, pause it; if already paused, it stays paused
            if ($lease->status === LeaseStatus::ACTIVE) {
                $lease->update(['status' => LeaseStatus::PAUSED]);
            }
            $driverId = $lease->driver_id;
        } else {
            $driverId = null;
        }

        // Create maintenance record
        MaintenanceService::create([
            'car_id' => $vehicle->id,
            'driver_id' => $driverId,
            'scheduled_date' => now()->toDateString(),
            'status' => 'in_progress',
            'description' => 'Vehicle moved to maintenance',
        ]);

        // Update vehicle status
        $vehicle->update(['status' => CarStatus::MAINTENANCE]);
    }

    return redirect()->back()->with('success', 'Vehicle moved to maintenance.');
}
}

