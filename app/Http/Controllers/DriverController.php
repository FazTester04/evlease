<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers.
     */
    public function index(Request $request)
    {
        $drivers = User::where('role', UserRole::DRIVER)
            ->with(['leases' => function ($q) {
                $q->where('status', 'active')->with('car');
            }])
            ->with(['documents' => function ($q) {
                $q->where('type', 'driver_license');
            }])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('driver_license', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($driver) {
                // Add computed attributes for easier access in Vue
                $driver->license_document = $driver->documents->first();
                $driver->active_lease = $driver->leases->first();
                return $driver;
            });

        return Inertia::render('drivers', [
            'drivers' => $drivers,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created driver.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|unique:users,email',
            'password'          => 'required|string|min:8',
            'phone'             => 'nullable|string|max:20',
            'date_of_birth'     => 'nullable|date',
            'address'           => 'nullable|string',
            'driver_license'    => 'nullable|string|max:50',
            'license_expiry'    => 'nullable|date',
            'license_document'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Create the user (driver)
        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'] ?? null,
            'password'        => Hash::make($validated['password']),
            'role'            => UserRole::DRIVER,
            'is_active'       => true,
            'phone'           => $validated['phone'] ?? null,
            'date_of_birth'   => $validated['date_of_birth'] ?? null,
            'address'         => $validated['address'] ?? null,
            'driver_license'  => $validated['driver_license'] ?? null,
        ]);

        // Handle license document upload
        if ($request->hasFile('license_document') && $request->filled('license_expiry')) {
            $path = $request->file('license_document')->store('driver-licenses', 'public');

            // Compute status based on expiry
            $expiry = Carbon::parse($validated['license_expiry']);
            $status = $this->calculateDocumentStatus($expiry);

            Document::create([
                'driver_id'    => $user->id,
                'name'         => 'Driver License',
                'type'         => 'driver_license',
                'file_path'    => $path,
                'expiry_date'  => $validated['license_expiry'],
                'status'       => $status,
            ]);
        }

        return redirect()->back()->with('success', 'Driver created successfully.');
    }

    /**
     * Update the specified driver.
     */
    public function update(Request $request, User $driver)
    {
        // Ensure we're dealing with a driver
        if ($driver->role !== UserRole::DRIVER) {
            abort(404);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|unique:users,email,' . $driver->id,
            'phone'             => 'nullable|string|max:20',
            'date_of_birth'     => 'nullable|date',
            'address'           => 'nullable|string',
            'driver_license'    => 'nullable|string|max:50',
            'license_expiry'    => 'nullable|date',
            'license_document'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $driver->update([
            'name'           => $validated['name'],
            'email'          => $validated['email'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'date_of_birth'  => $validated['date_of_birth'] ?? null,
            'address'        => $validated['address'] ?? null,
            'driver_license' => $validated['driver_license'] ?? null,
        ]);

        // Handle license document update
        if ($request->hasFile('license_document')) {
            // Delete old document
            $oldDoc = Document::where('driver_id', $driver->id)
                              ->where('type', 'driver_license')
                              ->first();
            if ($oldDoc) {
                Storage::disk('public')->delete($oldDoc->file_path);
                $oldDoc->delete();
            }

            $path = $request->file('license_document')->store('driver-licenses', 'public');
            $expiry = Carbon::parse($validated['license_expiry']);
            $status = $this->calculateDocumentStatus($expiry);

            Document::create([
                'driver_id'    => $driver->id,
                'name'         => 'Driver License',
                'type'         => 'driver_license',
                'file_path'    => $path,
                'expiry_date'  => $validated['license_expiry'],
                'status'       => $status,
            ]);
        } elseif ($request->filled('license_expiry')) {
            // Update expiry on existing document
            $doc = Document::where('driver_id', $driver->id)
                           ->where('type', 'driver_license')
                           ->first();
            if ($doc) {
                $doc->update([
                    'expiry_date' => $validated['license_expiry'],
                    'status'      => $this->calculateDocumentStatus(Carbon::parse($validated['license_expiry'])),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Driver updated successfully.');
    }

    /**
     * Remove the specified driver.
     */
    public function destroy(User $driver)
    {
        if ($driver->role !== UserRole::DRIVER) {
            abort(404);
        }

        // Delete associated license document
        $doc = Document::where('driver_id', $driver->id)
                       ->where('type', 'driver_license')
                       ->first();
        if ($doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $driver->delete();

        return redirect()->back()->with('success', 'Driver deleted successfully.');
    }

    /**
     * Calculate document status based on expiry date.
     */
    private function calculateDocumentStatus(Carbon $expiry): string
    {
        if ($expiry->isPast()) {
            return 'expired';
        }
        if ($expiry->diffInDays(now()) <= 30) {
            return 'expiring';
        }
        return 'valid';
    }
}