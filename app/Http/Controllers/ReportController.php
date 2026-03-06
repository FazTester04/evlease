<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportController extends Controller
{
    public function export()
    {
        // Define an anonymous class on the fly
        $export = new class implements FromCollection, WithHeadings {
            public function collection() {
                return Lease::with(['car', 'driver'])->get()->map(function($lease) {
                    return [
                        'ID' => $lease->id,
                        'Driver' => $lease->driver?->name,
                        'Plate' => $lease->car?->license_plate,
                        'Model' => $lease->car?->model,
                        'Monthly' => $lease->monthly_payment,
                        'Balance' => $lease->outstanding_balance,
                    ];
                });
            }

            public function headings(): array {
                return ['Lease ID', 'Driver Name', 'License Plate', 'Model', 'Monthly (RM)', 'Balance (RM)'];
            }
        };

        return Excel::download($export, 'fleet-report-' . now()->format('Y-m-d') . '.csv');
    }
}