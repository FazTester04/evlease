<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\LeaseStatus; // <-- added if you have this enum
use App\Models\Car;
use App\Models\Document;
use App\Models\Lease;          // <-- added
use App\Models\MaintenanceService; // <-- added
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use MohamedGaldi\ViltFilepond\Services\FilePondService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CarsController extends Controller
{
    protected FilePondService $filePondService;

    public function __construct(FilePondService $filePondService)
    {
        $this->filePondService = $filePondService;
    }

    public function index(Request $request)
    {
        $cars = Car::with(['documents', 'leases.documents'])
            ->when($request->search, function ($query, $search) {
                $query->where('license_plate', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($car) {
                $directDocs = $car->documents;
                $leaseDocs = $car->leases->flatMap->documents;
                $allDocs = $directDocs->concat($leaseDocs)->unique('id');

                $car->road_tax = $allDocs->where('type', 'road_tax')->sortByDesc('created_at')->first();
                $car->insurance = $allDocs->where('type', 'insurance')->sortByDesc('created_at')->first();
                $car->all_documents = $allDocs;

                return $car;
            });

        return Inertia::render('Fleet/Index', [
            'cars' => $cars,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cars/Edit', [
            'car' => null,
            'imageFiles' => [],
            'enums' => [
                'colors' => CarColor::forFrontend(),
                'statuses' => array_map(fn($status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color()
                ], CarStatus::cases()),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:255|unique:cars',
            'vin'           => 'nullable|string|max:255|unique:cars',
            'make'          => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'         => 'required|string|max:50',
            'remarks'       => 'nullable|string|max:50',
            'status'        => 'required|string',

            'insurance_provider'       => 'required|string|max:255',
            'insurance_policy_number'  => 'required|string|max:255',
            'insurance_expiry_date'    => 'required|date|after:today',
            'insurance_file'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',

            'road_tax_number'          => 'required|string|max:255',
            'road_tax_expiry_date'     => 'required|date|after:today',
            'road_tax_file'            => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $car = Car::create($validated);

        if ($request->hasFile('insurance_file')) {
            $path = $request->file('insurance_file')->store('car-documents', 'public');
            Document::create([
                'car_id'      => $car->id,
                'name'        => 'Insurance Policy',
                'type'        => 'insurance',
                'file_path'   => $path,
                'expiry_date' => $validated['insurance_expiry_date'],
                'status'      => 'valid',
            ]);
        }

        if ($request->hasFile('road_tax_file')) {
            $path = $request->file('road_tax_file')->store('car-documents', 'public');
            Document::create([
                'car_id'      => $car->id,
                'name'        => 'Road Tax',
                'type'        => 'road_tax',
                'file_path'   => $path,
                'expiry_date' => $validated['road_tax_expiry_date'],
                'status'      => 'valid',
            ]);
        }

        return redirect()->back()->with('success', 'Car added successfully.');
    }

    public function edit(Car $car): Response
    {
        $disk = config('vilt-filepond.storage_disk', 'public');
        $imageFiles = $car->files()
            ->where('collection', 'image')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'url' => Storage::url($f->path),
            ]);

        $insuranceDoc = $car->documents()->where('type', 'insurance')->first();
        $roadTaxDoc = $car->documents()->where('type', 'road_tax')->first();

        return Inertia::render('Admin/Cars/Edit', [
            'car' => $car,
            'imageFiles' => $imageFiles,
            'insuranceDoc' => $insuranceDoc,
            'roadTaxDoc' => $roadTaxDoc,
            'enums' => [
                'colors' => CarColor::forFrontend(),
                'statuses' => array_map(fn($status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ], CarStatus::cases()),
            ],
        ]);
    }

    public function update(Request $request, Car $car)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:255|unique:cars,license_plate,' . $car->id,
            'vin'           => 'nullable|string|max:255|unique:cars,vin,' . $car->id,
            'make'          => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'         => ['required', 'string', Rule::enum(CarColor::class)],
            'remarks'       => 'nullable|string|max:50',
            'status'        => ['required', 'string', Rule::enum(CarStatus::class)],

            'insurance_provider'       => 'nullable|string|max:255',
            'insurance_policy_number'  => 'nullable|string|max:255',
            'insurance_expiry_date'    => 'nullable|date|after:today',
            'insurance_file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

            'road_tax_number'          => 'nullable|string|max:255',
            'road_tax_expiry_date'     => 'nullable|date|after:today',
            'road_tax_file'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $car->update($validated);

        // Handle insurance document updates (unchanged)
        if ($request->hasFile('insurance_file')) {
            $old = Document::where('car_id', $car->id)->where('type', 'insurance')->first();
            if ($old) {
                Storage::disk('public')->delete($old->file_path);
                $old->delete();
            }

            $path = $request->file('insurance_file')->store('car-documents', 'public');
            Document::create([
                'car_id'      => $car->id,
                'name'        => 'Insurance Policy',
                'type'        => 'insurance',
                'file_path'   => $path,
                'expiry_date' => $validated['insurance_expiry_date'] ?? $car->insurance_expiry_date,
                'status'      => 'valid',
            ]);
        } elseif ($request->filled('insurance_expiry_date')) {
            $doc = Document::where('car_id', $car->id)->where('type', 'insurance')->first();
            if ($doc) {
                $doc->update(['expiry_date' => $validated['insurance_expiry_date']]);
            }
        }

        // Handle road tax document updates (unchanged)
        if ($request->hasFile('road_tax_file')) {
            $old = Document::where('car_id', $car->id)->where('type', 'road_tax')->first();
            if ($old) {
                Storage::disk('public')->delete($old->file_path);
                $old->delete();
            }

            $path = $request->file('road_tax_file')->store('car-documents', 'public');
            Document::create([
                'car_id'      => $car->id,
                'name'        => 'Road Tax',
                'type'        => 'road_tax',
                'file_path'   => $path,
                'expiry_date' => $validated['road_tax_expiry_date'] ?? $car->road_tax_expiry_date,
                'status'      => 'valid',
            ]);
        } elseif ($request->filled('road_tax_expiry_date')) {
            $doc = Document::where('car_id', $car->id)->where('type', 'road_tax')->first();
            if ($doc) {
                $doc->update(['expiry_date' => $validated['road_tax_expiry_date']]);
            }
        }

        // --- NEW: Handle transition to maintenance ---
        if ($car->wasChanged('status') && $car->status === CarStatus::MAINTENANCE->value) {
            // Find any active lease for this car
            $activeLease = Lease::where('car_id', $car->id)
                ->where('status', 'active') // use string if no enum, or LeaseStatus::ACTIVE->value
                ->first();

            if ($activeLease) {
                // Pause the lease
                $activeLease->update(['status' => 'paused']);

                // Create maintenance record with driver
                MaintenanceService::create([
                    'car_id'         => $car->id,
                    'driver_id'      => $activeLease->driver_id,
                    'scheduled_date' => now()->toDateString(),
                    'status'         => 'in_progress',
                    'description'    => 'Vehicle moved to maintenance via edit',
                ]);
            } else {
                // No active lease – record without driver
                MaintenanceService::create([
                    'car_id'         => $car->id,
                    'scheduled_date' => now()->toDateString(),
                    'status'         => 'in_progress',
                    'description'    => 'Vehicle moved to maintenance via edit (no active lease)',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Car updated successfully.');
    }

    public function destroy(Car $car)
    {
        foreach ($car->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }
        $car->delete();
        return redirect()->back()->with('success', 'Car deleted successfully.');
    }
}