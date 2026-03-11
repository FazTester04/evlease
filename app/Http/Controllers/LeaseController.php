<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Lease;
use App\Models\Car;
use App\Models\LeasePayment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;

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

        // Drivers with role driver AND no ongoing lease (active, paused, or pending)
        $availableDrivers = User::where('role', UserRole::DRIVER)
            ->whereDoesntHave('leases', function ($q) {
                $q->whereIn('status', ['active', 'paused', 'pending']);
            })
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

            // Update driver status to reflect active lease
            $driver = User::find($validated['driver_id']);
            $this->updateDriverStatus($driver);
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

        // Update driver status based on new lease status
        $this->updateDriverStatus($lease->driver);

        return redirect()->back()->with('success', 'Lease updated successfully.');
    }

    /**
     * Remove the specified lease from storage.
     */
    public function destroy(Lease $lease)
    {
        $wasActive = $lease->status === 'active';
        $driver = $lease->driver;
        $car = $lease->car;

        $lease->delete();

        // If the deleted lease was active, set the car back to available
        if ($wasActive) {
            $car->status = 'available';
            $car->save();
        }

        // Recalculate driver status (they may become available or unavailable)
        $this->updateDriverStatus($driver);

        return redirect()->back()->with('success', 'Lease deleted successfully.');
    }

    /**
     * Get detailed statement for a lease including financial calculations.
     *
     * @param Lease $lease
     * @return \Illuminate\Http\JsonResponse
     */
   public function getStatement(Lease $lease)
{
    // Load relationships (car, driver, lateFees)
    $lease->load('car', 'driver', 'lateFees');

    // Calculate number of months
    $start = Carbon::parse($lease->start_date);
    $end = $lease->end_date ? Carbon::parse($lease->end_date) : Carbon::now();
    $totalMonths = max(1, $start->diffInMonths($end));

    // (a) Total Lease Value
    $totalLeaseValue = $totalMonths * $lease->monthly_payment;

    // (d) Late charges
    $additionalLateCharges = $lease->lateFees->sum('amount');

    // 🔁 Direct query to get payments (bypass any relationship issues)
    $payments = LeasePayment::where('lease_id', $lease->id)->get();
    $paymentsMade = $payments->sum('amount');

    // (f) Total Payable = a + d - e
    $totalPayable = $totalLeaseValue + $additionalLateCharges - $paymentsMade;

    return response()->json([
        'lease' => $lease,
        'payments' => $payments,
        'late_fees' => $lease->lateFees,
        'total_lease_value' => $totalLeaseValue,
        'monthly_installment' => $lease->monthly_payment,
        'initial_deposit' => $lease->down_payment,
        'additional_late_charges' => $additionalLateCharges,
        'payments_made' => $paymentsMade,          // <-- ADDED
        'total_payable' => $totalPayable,          // <-- ADDED
        // Keep 'outstanding' for backward compatibility if needed
        'outstanding' => $totalPayable,
    ]);
}

    /**
     * Determine the correct status for a driver based on active lease and license.
     *
     * @param User $driver
     * @return string
     */
    private function determineDriverStatus(User $driver): string
    {
        // If they have an active lease, status is on_lease
        if ($driver->leases()->where('status', 'active')->exists()) {
            return 'on_lease';
        }

        // Check for valid license document
        $license = $driver->documents()->where('type', 'driver_license')->first();
        if (!$license || !$license->expiry_date || Carbon::parse($license->expiry_date)->isPast()) {
            return 'unavailable';
        }

        return 'available';
    }

    /**
     * Update a driver's status based on the determination logic.
     *
     * @param User $driver
     * @return void
     */
    private function updateDriverStatus(User $driver): void
    {
        $newStatus = $this->determineDriverStatus($driver);
        if ($driver->status !== $newStatus) {
            $driver->update(['status' => $newStatus]);
        }
    }
}