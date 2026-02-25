<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Lease;
use App\Models\Car;
use App\Models\Driver;
use App\Models\LeasePayment;
use Illuminate\Http\Request;
use App\Models\User;

class LeaseController extends Controller
{
    /**
     * Store a newly created lease.
     */
   public function store(Request $request)
{
    $validated = $request->validate([
        'car_id'          => 'required|exists:cars,id',
        'driver_id'       => 'required|exists:users,id',
        'start_date'      => 'required|date',
        'end_date'        => 'nullable|date|after:start_date',
        'monthly_payment' => 'required|numeric|min:0',
        'down_payment'    => 'nullable|numeric|min:0',   // <-- new field
        'status'          => 'required|in:active,ended,pending',
    ]);

    // Default down_payment to 0 if not provided
    $validated['down_payment'] = $validated['down_payment'] ?? 0;

    // Check if car already has an active lease
    $existingActive = Lease::where('car_id', $validated['car_id'])
                            ->where('status', 'active')
                            ->exists();
    if ($existingActive) {
        return back()->withErrors(['car_id' => 'This car already has an active lease.']);
    }

    $lease = Lease::create($validated);

    if ($validated['status'] === 'active') {
        $car = Car::find($validated['car_id']);
        $car->status = 'leased';
        $car->save();
    }

    return redirect()->back()->with('success', 'Lease created successfully.');
}

public function update(Request $request, Lease $lease)
{
    $validated = $request->validate([
        'start_date'      => 'required|date',
        'end_date'        => 'nullable|date|after:start_date',
        'monthly_payment' => 'required|numeric|min:0',
        'down_payment'    => 'nullable|numeric|min:0',
        'status'          => 'required|in:active,ended,pending',
    ]);

    $validated['down_payment'] = $validated['down_payment'] ?? 0;

    $oldStatus = $lease->status;
    $lease->update($validated);

    $car = $lease->car;

    if ($validated['status'] === 'active' && $oldStatus !== 'active') {
        $car->status = 'leased';
        $car->save();
    } elseif ($oldStatus === 'active' && $validated['status'] !== 'active') {
        $car->status = 'available';
        $car->save();
    }

    return redirect()->back()->with('success', 'Lease updated successfully.');
}

    /**
     * Remove the specified lease from storage.
     */
    public function destroy(Lease $lease)
    {
        $car = $lease->car;

        // If the deleted lease was active, set the car back to available
        if ($lease->status === 'active') {
            $car->status = 'available';
            $car->save();
        }

        $lease->delete();

        return redirect()->back()->with('success', 'Lease deleted successfully.');
    }
public function index(Request $request)
{
    $leases = Lease::with('car', 'driver')->get();
    $cars = Car::all(); // for dropdown
    $drivers = User::where('role', 'driver')->get(); // adjust to your driver model

    return Inertia::render('leases', [
        'leases' => $leases,
        'cars' => $cars,
        'drivers' => $drivers,
        'filters' => $request->only('search'),
    ]);
}
}
