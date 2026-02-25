<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Lease;
use App\Models\Driver;
use App\Models\Maintenance;
use Inertia\Inertia;
use Illuminate\Http\Request;

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
        ->get();

    $leases = Lease::with('car', 'driver')->get();

    return Inertia::render('Fleet/Index', [
        'cars' => $cars,
        'leases' => $leases,
        'filters' => $request->only('search'),
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

}