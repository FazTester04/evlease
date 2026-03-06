<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Inertia\Inertia;
use App\Models\Lease;
use App\Enums\CarStatus;
use App\Enums\LeaseStatus;
use App\Models\MaintenanceService;


class VehicleController extends Controller
{
    public function index()
    {
        $availableCars = Car::where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Driver/Vehicles/Index', [
            'vehicles' => $availableCars,
        ]);
    }
public function setMaintenance(Car $vehicle)
{
    if ($vehicle->status !== CarStatus::MAINTENANCE) {
        $activeLease = Lease::where('car_id', $vehicle->id)
            ->where('status', LeaseStatus::ACTIVE)
            ->first();

        if ($activeLease) {
            $activeLease->update(['status' => LeaseStatus::PAUSED]);

            MaintenanceService::create([
                'car_id' => $vehicle->id,
                'driver_id' => $activeLease->driver_id,
                'scheduled_date' => now()->toDateString(),
                'status' => 'in_progress',
                'description' => 'Vehicle moved to maintenance',
            ]);
        } else {
            MaintenanceService::create([
                'car_id' => $vehicle->id,
                'scheduled_date' => now()->toDateString(),
                'status' => 'in_progress',
                'description' => 'Vehicle moved to maintenance (no lease)',
            ]);
        }

        $vehicle->update(['status' => CarStatus::MAINTENANCE]);
    }

    return redirect()->back()->with('success', 'Vehicle moved to maintenance.');
}
}
