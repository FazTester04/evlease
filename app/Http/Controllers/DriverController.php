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
            // Search by name, email, license, or IC
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('driver_license', 'like', "%{$search}%")
                        ->orWhere('ic_number', 'like', "%{$search}%");
                });
            })
            // Eager load relationships
            ->with([
                'leases' => function ($q) {
                    $q->where('status', 'active')->with('car');
                },
                'documents',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            // Transform for Vue
            ->map(function ($driver) {
                // Helper to get document by type and attach URL
                $mapDoc = function (string $type) use ($driver) {
                    $doc = $driver->documents->where('type', $type)->first();
                    if ($doc) {
                        $doc->file_url = $doc->file_path ? url('/files/' . $doc->file_path) : null;
                    }

                    return $doc;
                };

                $driver->license_document = $mapDoc('driver_license');
                $driver->ic_document = $mapDoc('ic');
                $driver->active_lease = $driver->leases->first();

                // Derive availability status (order matters):
                // - on_lease: has active lease (show in "On Lease" filter regardless of license)
                // - unavailable: no valid license — must renew before assignable to lease
                // - available: valid license and no active lease
                $licenseValid = $driver->license_document
                    && $driver->license_document->status !== 'expired'
                    && $driver->license_document->expiry_date
                    && \Carbon\Carbon::parse($driver->license_document->expiry_date)->isFuture();

                if ($driver->active_lease) {
                    $availability = 'on_lease';
                } elseif (! $licenseValid) {
                    $availability = 'unavailable';
                } else {
                    $availability = 'available';
                }

                // Use setAttribute so it is included when model is serialized to JSON for Inertia
                $driver->setAttribute('availability_status', $availability);

                return $driver;
            });

        // Server-side filter by availability
        if ($request->filled('status')) {
            $status = $request->string('status')->lower()->toString();
            $drivers = $drivers->filter(function ($d) use ($status) {
                return $d->getAttribute('availability_status') === $status;
            })->values();
        }

        return Inertia::render('drivers', [
            'drivers' => $drivers,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    /**
     * Store a newly created driver.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|string|min:8',
            'phone'            => ['required', 'regex:/^\\+60\\d{9,10}$/'],
            'date_of_birth'    => 'required|date|before:today',
            'address'          => 'nullable|string',
            'remarks'          => 'nullable|string',
            'driver_license'   => 'required|string|max:50',
            'ic_number'        => 'required|digits:12|unique:users,ic_number',
            'ic_file'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'license_expiry'   => 'required|date|after:today',
            'license_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Create the user
        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => Hash::make($validated['password']),
            'role'           => UserRole::DRIVER,
            'is_active'      => true,
            'phone'          => $validated['phone'],
            'date_of_birth'  => $validated['date_of_birth'],
            'address'        => $validated['address'] ?? null,
            'remarks'        => $validated['remarks'] ?? null,
            'driver_license' => $validated['driver_license'],
            'ic_number'      => $validated['ic_number'],
        ]);

        // Store IC document (always, since required)
        if ($request->hasFile('ic_file')) {
            $icPath = $request->file('ic_file')->store('driver-ics', 'public');
            Document::create([
                'driver_id' => $user->id,
                'name'      => 'Identification Card',
                'type'      => 'ic',
                'file_path' => $icPath,
                'status'    => 'valid',
            ]);
        }

        // Store license document
        if ($request->hasFile('license_document')) {
            $licensePath = $request->file('license_document')->store('driver-licenses', 'public');
            $expiry = Carbon::parse($validated['license_expiry']);
            $status = $this->calculateDocumentStatus($expiry);

            Document::create([
                'driver_id'   => $user->id,
                'name'        => 'Driver License',
                'type'        => 'driver_license',
                'file_path'   => $licensePath,
                'expiry_date' => $validated['license_expiry'],
                'status'      => $status,
            ]);
        }

        return redirect()->back()->with('success', 'Driver added.');
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
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email|unique:users,email,' . $driver->id,
            'phone'            => ['nullable', 'regex:/^\\+60\\d{9,10}$/'],
            'date_of_birth'    => 'nullable|date|before:today',
            'address'          => 'nullable|string',
            'remarks'          => 'nullable|string',
            'driver_license'   => 'nullable|string|max:50',
            'license_expiry'   => 'nullable|date|after:today',
            'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $driver->update([
            'name'           => $validated['name'],
            'email'          => $validated['email'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'date_of_birth'  => $validated['date_of_birth'] ?? null,
            'address'        => $validated['address'] ?? null,
            'remarks'        => $validated['remarks'] ?? null,
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
