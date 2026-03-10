<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Lease;
use App\Models\Car;
use App\Models\LeasePayment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Enums\UserRole;

class LeaseController extends Controller
{
    /**
     * Display a listing of leases.
     */
    public function index(Request $request)
    {
        $leases = Lease::with('car', 'driver')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('car', function ($q) use ($search) {
                    $q->where('license_plate', 'like', "%{$search}%");
                })->orWhereHas('driver', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Cars that are physically available AND have no active lease
        $availableCars = Car::where('status', 'available')
            ->whereDoesntHave('leases', function ($q) {
                $q->where('status', 'active');
            })
            ->get();

        // Drivers with valid license and no ongoing lease — unavailable (expired/no license) cannot be assigned
        $availableDrivers = User::where('role', UserRole::DRIVER)
            ->whereDoesntHave('leases', function ($q) {
                $q->whereIn('status', ['active', 'paused', 'pending']);
            })
            ->whereHas('documents', function ($q) {
                $q->where('type', 'driver_license')
                    ->where('expiry_date', '>', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('leases', [
            'leases' => $leases,
            'cars'   => $availableCars,
            'drivers' => $availableDrivers,
            'filters' => $request->only('search'),
        ]);
    }

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
            'down_payment'    => 'nullable|numeric|min:0',
            'status'          => 'required|in:active,ended,pending',
        ]);

        $validated['down_payment'] = $validated['down_payment'] ?? 0;

        // Check if car already has an active lease
        if (Lease::where('car_id', $validated['car_id'])->where('status', 'active')->exists()) {
            return back()->withErrors(['car_id' => 'This car already has an active lease.']);
        }

        // Check if driver already has an ongoing lease (active, paused, or pending)
        if (Lease::where('driver_id', $validated['driver_id'])
                 ->whereIn('status', ['active', 'paused', 'pending'])
                 ->exists()) {
            return back()->withErrors(['driver_id' => 'This driver already has an active, paused, or pending lease.']);
        }

        // Driver must have a valid (non-expired) license to be assigned a lease
        $driverHasValidLicense = \App\Models\Document::where('driver_id', $validated['driver_id'])
            ->where('type', 'driver_license')
            ->where('expiry_date', '>', now()->toDateString())
            ->exists();
        if (! $driverHasValidLicense) {
            return back()->withErrors(['driver_id' => 'This driver does not have a valid license. License must be renewed before they can be assigned a lease.']);
        }

        $lease = Lease::create($validated);

        // Create the first pending payment (if needed)
        $lease->payments()->create([
            'amount'   => $lease->monthly_payment,
            'due_date' => $lease->start_date,
            'status'   => 'pending',
        ]);

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
            'down_payment'    => 'nullable|numeric|min:0',
            'status'          => 'required|in:active,ended,pending',
        ]);

        $validated['down_payment'] = $validated['down_payment'] ?? 0;

        $oldStatus = $lease->status;
        $lease->update($validated);

        $car = $lease->car;

        // Update car status based on lease status change
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
}