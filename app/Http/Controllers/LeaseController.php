<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
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

    // Optional: check if car already has an active lease
    $existingActive = Lease::where('car_id', $validated['car_id'])
                            ->where('status', 'active')
                            ->exists();
    if ($existingActive) {
        return back()->withErrors(['car_id' => 'This car already has an active lease.']);
    }

    Lease::create($validated);

    return redirect()->back()->with('success', 'Lease created successfully.');
}
    public function update(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after:start_date',
            'monthly_payment' => 'required|numeric|min:0',
            'status'          => 'required|in:active,ended,pending',
        ]);

        $lease->update($validated);

        return redirect()->back()->with('success', 'Lease updated successfully.');
    }

    /**
     * Remove the specified lease from storage.
     */
    public function destroy(Lease $lease)
    {
        $lease->delete();

        return redirect()->back()->with('success', 'Lease deleted successfully.');
    }
}