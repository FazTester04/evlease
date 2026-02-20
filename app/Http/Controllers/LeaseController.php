<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Car;
use Illuminate\Http\Request;

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
            'status'          => 'required|in:active,ended,pending',
        ]);

        // Check if car already has an active lease
        $existingActive = Lease::where('car_id', $validated['car_id'])
                                ->where('status', 'active')
                                ->exists();
        if ($existingActive) {
            return back()->withErrors(['car_id' => 'This car already has an active lease.']);
        }

        $lease = Lease::create($validated);

        // If the new lease is active, update the car's status to 'leased'
        if ($validated['status'] === 'active') {
            $car = Car::find($validated['car_id']);
            $car->status = 'leased';
            $car->save();
        }

        return redirect()->back()->with('success', 'Lease created successfully.');
    }

    /**
     * Update the specified lease.
     */
    public function update(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after:start_date',
            'monthly_payment' => 'required|numeric|min:0',
            'status'          => 'required|in:active,ended,pending',
        ]);

        $oldStatus = $lease->status;
        $lease->update($validated);

        $car = $lease->car;

        // If the status changed to active, set car to leased
        if ($validated['status'] === 'active' && $oldStatus !== 'active') {
            $car->status = 'leased';
            $car->save();
        }
        // If the status changed from active to ended/pending, set car back to available
        elseif ($oldStatus === 'active' && $validated['status'] !== 'active') {
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
}