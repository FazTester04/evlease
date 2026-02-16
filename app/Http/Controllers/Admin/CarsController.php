<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
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
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        
        // Get status counts for the filter
        $statusCounts = Car::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $cars = Car::query()->with('files')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('make', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('license_plate', 'like', "%{$search}%");
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $statuses = collect(CarStatus::cases())->mapWithKeys(function ($status) use ($statusCounts) {
            return [
                $status->value => [
                    'label' => $status->label(),
                    'count' => $statusCounts[$status->value] ?? 0,
                    'color' => $status->color(),
                ]
            ];
        })->toArray();

        return Inertia::render('Admin/Cars/Index', [
            'cars' => $cars,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status,
            ],
            'statuses' => $statuses,
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
                'fuelTypes' => FuelType::forFrontend(),
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
            'color'         => ['required', 'string', Rule::enum(CarColor::class)],
            'description'   => 'nullable|string',
            'status'        => ['required', 'string', Rule::enum(CarStatus::class)],
        ]);

        Car::create($validated);

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
            ->map(fn ($f) => [
                'id' => $f->id,
                'url' => Storage::url($f->path),
            ]);

        return Inertia::render('Admin/Cars/Edit', [
            'car' => $car,
            'imageFiles' => $imageFiles,
            'enums' => [
                'colors' => CarColor::forFrontend(),
                'fuelTypes' => FuelType::forFrontend(),
                'statuses' => array_map(fn($status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color()
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
