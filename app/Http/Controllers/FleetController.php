<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Lease;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\LeasePayment;
use Illuminate\Support\Facades\Storage;

class FleetController extends Controller
{
    /**
     * Display the fleet management page.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        // ---------- CARS ----------
        $cars = Car::with([
                'leases' => function ($q) {
                    $q->where('status', 'active')->latest('start_date');
                },
                'leases.driver',
                'leases.payments' => function ($q) {
                    $q->latest('due_date');
                },
                'maintenanceServices',
                'documents'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('license_plate', 'LIKE', "%{$search}%")
                      ->orWhere('vin', 'LIKE', "%{$search}%")
                      ->orWhere('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('license_plate')
            ->get()
            ->map(function ($car) {
                $activeLease = $car->leases->first();
                $currentDriver = $activeLease?->driver;

                // Latest road tax document
                $roadTax = $car->documents
                    ->where('name', 'Road Tax')
                    ->sortByDesc('expiry_date')
                    ->first();

                // Latest insurance document
                $insurance = $car->documents
                    ->where('name', 'Insurance')
                    ->sortByDesc('expiry_date')
                    ->first();

                // Payment status from active lease
                $paymentStatus = 'N/A';
                $nextPaymentDue = null;
                if ($activeLease) {
                    $lastPayment = $activeLease->payments->first();
                    if ($lastPayment) {
                        if ($lastPayment->status === 'paid') {
                            $paymentStatus = 'Paid on Time';
                        } elseif ($lastPayment->status === 'pending') {
                            $paymentStatus = $lastPayment->due_date->isPast() ? 'Overdue' : 'Pending';
                        }
                    }
                    $nextPayment = $activeLease->payments
                        ->where('status', 'pending')
                        ->where('due_date', '>=', now())
                        ->sortBy('due_date')
                        ->first();
                    $nextPaymentDue = $nextPayment?->due_date;
                }

                // Service status (using Collection methods)
                $serviceStatus = 'Up to Date';
                $nextServiceDate = null;
                $overdueService = $car->maintenanceServices->first(function ($service) {
                    return $service->status === 'overdue' ||
                           ($service->status === 'scheduled' && $service->scheduled_date->lt(now()));
                });
                if ($overdueService) {
                    $serviceStatus = 'Overdue';
                    $nextServiceDate = $overdueService->scheduled_date;
                } else {
                    $nextService = $car->maintenanceServices
                        ->filter(function ($service) {
                            return $service->status === 'scheduled' && $service->scheduled_date->gte(now());
                        })
                        ->sortBy('scheduled_date')
                        ->first();
                    if ($nextService) {
                        $daysUntil = now()->diffInDays($nextService->scheduled_date, false);
                        $serviceStatus = $daysUntil <= 30 ? 'Due Soon' : 'Up to Date';
                        $nextServiceDate = $nextService->scheduled_date;
                    }
                }

                return [
                    'id' => $car->id,
                    'license_plate' => $car->license_plate,
                    'vin' => $car->vin,
                    'make' => $car->make,
                    'model' => $car->model,
                    'year' => $car->year,
                    'color' => $car->color,
                    'status' => $car->status->label(),
                    'status_color' => $car->status->color(),
                    'road_tax' => [
                        'status' => $roadTax?->status ?? 'Not Uploaded',
                        'expiry_date' => $roadTax?->expiry_date?->format('m/d/Y'),
                    ],
                    'insurance' => [
                        'status' => $insurance?->status ?? 'Not Uploaded',
                        'expiry_date' => $insurance?->expiry_date?->format('m/d/Y'),
                    ],
                    'current_driver' => $currentDriver ? [
                        'name' => $currentDriver->name,
                        'driver_license' => $currentDriver->driver_license,
                        'monthly_payment' => $activeLease?->monthly_payment,
                    ] : null,
                    'active_lease_id' => $activeLease?->id,   // <-- ADDED FOR DIRECT EDITING
                    'payment_status' => $paymentStatus,
                    'next_payment_due' => $nextPaymentDue?->format('m/d/Y'),
                    'service_status' => $serviceStatus,
                    'next_service_date' => $nextServiceDate?->format('m/d/Y'),
                ];
            });

        // ---------- LEASES ----------
        $leases = Lease::with(['car', 'driver'])
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($lease) {
                $paymentStatus = $this->getLeasePaymentStatus($lease);
                return [
                    'id' => $lease->id,
                    'car' => [
                        'license_plate' => $lease->car->license_plate,
                        'make' => $lease->car->make,
                        'model' => $lease->car->model,
                    ],
                    'driver' => [
                        'name' => $lease->driver->name ?? 'Unknown',
                        'driver_license' => $lease->driver->driver_license ?? 'N/A',
                    ],
                    'start_date' => $lease->start_date->format('m/d/Y'),
                    'end_date' => $lease->end_date?->format('m/d/Y') ?? 'Ongoing',
                    'monthly_payment' => $lease->monthly_payment,
                    'payment_status' => $paymentStatus,
                ];
            });

        // ---------- DOCUMENTS ----------
        $documents = Document::with(['car', 'driver'])
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'expiry_date' => $doc->expiry_date?->format('m/d/Y'),
                    'status' => $doc->status,
                    'status_label' => ucfirst($doc->status),
                    'car' => $doc->car ? [
                        'license_plate' => $doc->car->license_plate,
                    ] : null,
                    'driver' => $doc->driver ? [
                        'name' => $doc->driver->name,
                    ] : null,
                ];
                
            });

            $payments = LeasePayment::with(['lease.car', 'lease.driver'])
    ->orderBy('paid_date', 'desc')
    ->get()
    ->map(function ($payment) {
        return [
            'id' => $payment->id,
            'lease_id' => $payment->lease_id,
            'car' => $payment->lease->car ? [
                'license_plate' => $payment->lease->car->license_plate,
                'make' => $payment->lease->car->make,
                'model' => $payment->lease->car->model,
            ] : null,
            'driver' => $payment->lease->driver ? [
                'name' => $payment->lease->driver->name,
                'driver_license' => $payment->lease->driver->driver_license,
            ] : null,
            'amount' => number_format($payment->amount, 2),
            'paid_date' => $payment->paid_date?->format('m/d/Y'),
            'due_date' => $payment->due_date?->format('m/d/Y'),
            'status' => $payment->status,
            'proof_url' => $payment->proof_path ? Storage::url($payment->proof_path) : null,
        ];
    });
       $drivers = User::drivers()->get(['id', 'name', 'driver_license']); 
 
            return Inertia::render('Fleet/Index', [
    'cars'      => $cars,
    'leases'    => $leases,
    'documents' => $documents,
    'payments'  => $payments,
    'drivers'   => $drivers,   
    'filters'   => ['search' => $search],
]);
    }

    /**
     * Helper to determine the overall payment status for a lease.
     */
    private function getLeasePaymentStatus($lease)
    {
        $lastPayment = $lease->payments()->latest('due_date')->first();
        if (!$lastPayment) {
            return 'No Payment';
        }
        if ($lastPayment->status === 'paid') {
            return 'Paid on Time';
        }
        if ($lastPayment->status === 'pending') {
            return $lastPayment->due_date->isPast() ? 'Overdue' : 'Pending';
        }
        return ucfirst($lastPayment->status);
    }
    
}