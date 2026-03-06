<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use MohamedGaldi\ViltFilepond\Services\FilePondService;
use Illuminate\Support\Facades\Storage;

class CarsController extends Controller
{
    protected FilePondService $filePondService;

    public function __construct(FilePondService $filePondService)
    {
        $this->filePondService = $filePondService;
    }

    /**
     * Display a listing of cars.
     */
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
            // Merge direct documents and those from leases
            $directDocs = $car->documents;
            $leaseDocs = $car->leases->flatMap->documents;
            $allDocs = $directDocs->concat($leaseDocs)->unique('id');

            // Set the specific document types expected by the Vue template
            $car->road_tax = $allDocs->where('type', 'road_tax')->sortByDesc('created_at')->first();
            $car->insurance = $allDocs->where('type', 'insurance')->sortByDesc('created_at')->first();

            // Optionally keep all_documents for other uses
            $car->all_documents = $allDocs;

            return $car;
        });

    return Inertia::render('Fleet/Index', [
        'cars' => $cars, // <-- must match the prop name in Vue (props.cars)
        'filters' => $request->only(['search', 'status']),
    ]);
}
    /**
     * Show the form for creating a new car.
     */
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

    /**
     * Store a newly created car in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:255|unique:cars',
            'vin'           => 'nullable|string|max:255|unique:cars',
            'make'          => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'         => 'required|string|max:50',
            'remarks'       => 'string|max:50',
            'status'        => 'required|string',
        ]);

        Car::create($validated);

        // Using back() ensures the driver stays on the same list view
        return redirect()->back()->with('success', 'Car added successfully.');
    }

    /**
     * Show the form for editing the specified car.
     */
    public function edit(Car $car): Response
    {
        // Provide initial image files for FilePond (only the 'image' collection)
        $disk = config('vilt-filepond.storage_disk', 'public');
        $imageFiles = $car->files()
            ->where('collection', 'image')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'url' => Storage::url($f->path),
            ]);

        return Inertia::render('Admin/Cars/Edit', [
            'car' => $car,
            'imageFiles' => $imageFiles,
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

    /**
     * Update the specified car in storage.
     */
    public function update(Request $request, Car $car)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:255|unique:cars,license_plate,' . $car->id,
            'vin'           => 'nullable|string|max:255|unique:cars,vin,' . $car->id,
            'make'          => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'         => ['required', 'string', Rule::enum(CarColor::class)],
            'description'   => 'nullable|string',
            'status'        => ['required', 'string', Rule::enum(CarStatus::class)],
        ]);

        $car->update($validated);

        return redirect()->back()->with('success', 'Car updated successfully.');
    }

    /**
     * Remove the specified car from storage.
     */
    public function destroy(Car $car)
    {
        $car->delete();
        return redirect()->back()->with('success', 'Car deleted successfully.');
    }
}
