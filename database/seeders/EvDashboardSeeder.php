<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\User;
use App\Models\Lease;
use App\Models\LeasePayment;
use App\Models\MaintenanceService;
use App\Models\Document;
use App\Enums\UserRole;
use App\Enums\CarStatus;
use Carbon\Carbon;

class EvDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. CARS – 4 Leased, 1 Maintenance, 1 Available
        // =====================================================================
        $cars = [
            [
                'license_plate' => 'EV-1001',
                'vin'           => '5YJ3E1EA1KF123456',
                'make'          => 'Tesla',
                'model'         => 'Model 3',
                'year'          => 2023,
                'color'         => 'white',
                'remarks'       => 'Long-term lease vehicle',
                'status'        => CarStatus::LEASED->value,
            ],
            [
                'license_plate' => 'EV-1002',
                'vin'           => '5YJYGDE2MF234567',
                'make'          => 'Tesla',
                'model'         => 'Model Y',
                'year'          => 2024,
                'color'         => 'Blue',
                'remarks'       => 'Long-term lease vehicle',
                'status'        => CarStatus::LEASED->value,
            ],
            [
                'license_plate' => 'EV-1003',
                'vin'           => '7FCVQAA11PU345678',
                'make'          => 'Nissan',
                'model'         => 'Leaf',
                'year'          => 2022,
                'color'         => 'Silver',
                'remarks'       => 'Long-term lease vehicle',
                'status'        => CarStatus::LEASED->value,
            ],
            [
                'license_plate' => 'EV-1004',
                'vin'           => '3FMTK33U8MMA456789',
                'make'          => 'Chevy',
                'model'         => 'Bolt',
                'year'          => 2023,
                'color'         => 'Red',
                'remarks'       => 'Long-term lease vehicle',
                'status'        => CarStatus::LEASED->value,
            ],
            [
                'license_plate' => 'EV-1005',
                'vin'           => '1G4FZ65O5N4567890',
                'make'          => 'Ford',
                'model'         => 'Mustang Mach-E',
                'year'          => 2024,
                'color'         => 'Blue',
                'remarks'       => 'Long-term lease vehicle',
                'status'        => CarStatus::LEASED->value,
            ],
            [
                'license_plate' => 'EV-1006',
                'vin'           => 'KM8J3CA46LU567890',
                'make'          => 'Hyundai',
                'model'         => 'Kona',
                'year'          => 2021,
                'color'         => 'Green',
                'remarks'       => 'In maintenance',
                'status'        => CarStatus::MAINTENANCE->value,
            ],
            [
                'license_plate' => 'EV-1007',
                'vin'           => 'WA1L2AFP2JA678901',
                'make'          => 'Audi',
                'model'         => 'e-tron',
                'year'          => 2023,
                'color'         => 'Black',
                'remarks'       => 'Available for leasing',
                'status'        => CarStatus::AVAILABLE->value,
            ],
        ];

        foreach ($cars as $carData) {
            Car::firstOrCreate(
                ['license_plate' => $carData['license_plate']],
                $carData
            );
        }

        // =====================================================================
        // 2. DRIVERS (Users with role = driver)
        // =====================================================================
        $drivers = [
            [
                'name'            => 'Robert Chen',
                'email'           => 'robert.chen@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-003',
            ],
            [
                'name'            => 'Maria Garcia',
                'email'           => 'maria.garcia@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-002',
            ],
            [
                'name'            => 'Michael Brown',
                'email'           => 'michael.brown@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-005',
            ],
            [
                'name'            => 'Sarah Johnson',
                'email'           => 'sarah.j@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-004',
            ],
            [
                'name'            => 'James Wilson',
                'email'           => 'james.wilson@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-001',
            ],
            [
                'name'            => 'Patricia Johnson',
                'email'           => 'patricia.j@example.com',
                'password'        => bcrypt('password'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DRV-006',
            ],
            // Add driver from DemoUsersSeeder
            [
                'name'            => 'Driver User',
                'email'           => 'driver@xperts.my',
                'password'        => bcrypt('Xperts12345'),
                'role'            => UserRole::DRIVER->value,
                'is_active'       => true,
                'driver_license'  => 'DL123456',
                'phone'           => '+60123456789',
                'date_of_birth'   => '1990-01-01',
                'address'         => '123 Jalan Example, Kuala Lumpur',
            ],
        ];

        foreach ($drivers as $driverData) {
            User::firstOrCreate(
                ['email' => $driverData['email']],
                $driverData
            );
        }

        // =====================================================================
        // 3. LEASES (Active leases for 5 cars)
        // =====================================================================
        $leaseData = [
            [
                'car_plate'       => 'EV-1001',
                'driver_email'    => 'james.wilson@example.com',
                'start_date'      => '2025-12-01',
                'end_date'        => '2026-11-30',
                'monthly_payment' => 499.00,
                'down_payment'    => 1000.00,
                'status'          => 'active',
            ],
            [
                'car_plate'       => 'EV-1002',
                'driver_email'    => 'patricia.j@example.com',
                'start_date'      => '2025-12-15',
                'end_date'        => '2026-12-14',
                'monthly_payment' => 499.00,
                'down_payment'    => 1000.00,
                'status'          => 'active',
            ],
            [
                'car_plate'       => 'EV-1003',
                'driver_email'    => 'maria.garcia@example.com',
                'start_date'      => '2026-01-01',
                'end_date'        => '2026-12-31',
                'monthly_payment' => 499.00,
                'down_payment'    => 1000.00,
                'status'          => 'active',
            ],
            [
                'car_plate'       => 'EV-1004',
                'driver_email'    => 'michael.brown@example.com',
                'start_date'      => '2026-01-15',
                'end_date'        => '2027-01-14',
                'monthly_payment' => 499.00,
                'down_payment'    => 1000.00,
                'status'          => 'active',
            ],
            [
                'car_plate'       => 'EV-1005',
                'driver_email'    => 'robert.chen@example.com',
                'start_date'      => '2025-12-01',
                'end_date'        => '2026-11-30',
                'monthly_payment' => 549.00,
                'down_payment'    => 1200.00,
                'status'          => 'active',
            ],
        ];

        $leases = [];
        foreach ($leaseData as $data) {
            $car = Car::where('license_plate', $data['car_plate'])->first();
            $driver = User::where('email', $data['driver_email'])->first();
            if (!$car || !$driver) continue;

            $lease = Lease::firstOrCreate(
                ['car_id' => $car->id, 'driver_id' => $driver->id],
                [
                    'start_date'      => $data['start_date'],
                    'end_date'        => $data['end_date'],
                    'monthly_payment' => $data['monthly_payment'],
                    'down_payment'    => $data['down_payment'],
                    'status'          => $data['status'],
                ]
            );
            $leases[$data['car_plate']] = $lease;
        }

        // =====================================================================
        // 4. LEASE PAYMENTS
        // =====================================================================
        if (isset($leases['EV-1001'])) {
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1001']->id, 'due_date' => '2026-01-01'],
                ['paid_date' => '2026-01-01', 'amount' => 499.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1001']->id, 'due_date' => '2026-02-01'],
                ['paid_date' => '2026-02-01', 'amount' => 499.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1001']->id, 'due_date' => '2026-03-01'],
                ['paid_date' => null, 'amount' => 499.00, 'status' => 'pending']
            );
        }

        if (isset($leases['EV-1002'])) {
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1002']->id, 'due_date' => '2026-01-15'],
                ['paid_date' => '2026-01-15', 'amount' => 499.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1002']->id, 'due_date' => '2026-02-15'],
                ['paid_date' => null, 'amount' => 499.00, 'status' => 'pending']
            );
        }

        if (isset($leases['EV-1003'])) {
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1003']->id, 'due_date' => '2026-01-01'],
                ['paid_date' => '2026-01-04', 'amount' => 499.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1003']->id, 'due_date' => '2026-02-01'],
                ['paid_date' => null, 'amount' => 499.00, 'status' => 'pending']
            );
        }

        if (isset($leases['EV-1004'])) {
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1004']->id, 'due_date' => '2026-01-15'],
                ['paid_date' => '2026-01-16', 'amount' => 499.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1004']->id, 'due_date' => '2026-02-15'],
                ['paid_date' => null, 'amount' => 499.00, 'status' => 'pending']
            );
        }

        if (isset($leases['EV-1005'])) {
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1005']->id, 'due_date' => '2025-12-01'],
                ['paid_date' => '2025-12-01', 'amount' => 549.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1005']->id, 'due_date' => '2026-01-01'],
                ['paid_date' => '2026-01-05', 'amount' => 549.00, 'status' => 'paid']
            );
            LeasePayment::firstOrCreate(
                ['lease_id' => $leases['EV-1005']->id, 'due_date' => '2026-02-01'],
                ['paid_date' => null, 'amount' => 549.00, 'status' => 'pending']
            );
        }

        // =====================================================================
        // 5. MAINTENANCE SERVICES (using correct status values)
        // =====================================================================
        $carMaintenance = Car::where('license_plate', 'EV-1006')->first();
        if ($carMaintenance) {
            MaintenanceService::firstOrCreate(
                ['car_id' => $carMaintenance->id, 'description' => 'Battery check'],
                [
                    'scheduled_date' => '2026-02-10',
                    'status'         => 'in_progress', // not overdue
                ]
            );
        }

        $carOverdue1 = Car::where('license_plate', 'EV-1003')->first();
        if ($carOverdue1) {
            MaintenanceService::firstOrCreate(
                ['car_id' => $carOverdue1->id, 'description' => 'Tire rotation'],
                [
                    'scheduled_date' => '2026-01-20',
                    'status'         => 'completed', // or in_progress? Let's set completed to avoid overdue
                ]
            );
        }

        $carOverdue2 = Car::where('license_plate', 'EV-1004')->first();
        if ($carOverdue2) {
            MaintenanceService::firstOrCreate(
                ['car_id' => $carOverdue2->id, 'description' => 'Brake inspection'],
                [
                    'scheduled_date' => '2026-01-25',
                    'status'         => 'completed',
                ]
            );
        }

        // =====================================================================
        // 6. DOCUMENTS – without status, with correct types, some linked to leases
        // =====================================================================
        $car1 = Car::where('license_plate', 'EV-1001')->first();
        if ($car1) {
            Document::firstOrCreate(
                ['car_id' => $car1->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance Certificate',
                    'file_path'   => 'documents/dummy1.pdf',
                    'expiry_date' => '2026-12-31',
                ]
            );
        }

        $car2 = Car::where('license_plate', 'EV-1002')->first();
        if ($car2) {
            Document::firstOrCreate(
                ['car_id' => $car2->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance Certificate',
                    'file_path'   => 'documents/dummy2.pdf',
                    'expiry_date' => Carbon::now()->addDays(15),
                ]
            );
        }

        $car3 = Car::where('license_plate', 'EV-1003')->first();
        if ($car3) {
            Document::firstOrCreate(
                ['car_id' => $car3->id, 'type' => 'road_tax'],
                [
                    'name'        => 'Road Tax',
                    'file_path'   => 'documents/dummy3.pdf',
                    'expiry_date' => Carbon::now()->addDays(15),
                ]
            );
            Document::firstOrCreate(
                ['car_id' => $car3->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance Certificate',
                    'file_path'   => 'documents/dummy4.pdf',
                    'expiry_date' => Carbon::now()->subDays(5), // expired
                ]
            );
        }

        $car4 = Car::where('license_plate', 'EV-1004')->first();
        if ($car4) {
            Document::firstOrCreate(
                ['car_id' => $car4->id, 'type' => 'road_tax'],
                [
                    'name'        => 'Road Tax',
                    'file_path'   => 'documents/dummy5.pdf',
                    'expiry_date' => '2026-10-15',
                ]
            );
            Document::firstOrCreate(
                ['car_id' => $car4->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance Certificate',
                    'file_path'   => 'documents/dummy6.pdf',
                    'expiry_date' => Carbon::now()->subDays(10), // expired
                ]
            );
        }

        $car5 = Car::where('license_plate', 'EV-1005')->first();
        if ($car5) {
            Document::firstOrCreate(
                ['car_id' => $car5->id, 'type' => 'road_tax'],
                [
                    'name'        => 'Road Tax',
                    'file_path'   => 'documents/dummy7.pdf',
                    'expiry_date' => Carbon::now()->addDays(53),
                ]
            );
            Document::firstOrCreate(
                ['car_id' => $car5->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance Certificate',
                    'file_path'   => 'documents/dummy8.pdf',
                    'expiry_date' => Carbon::now()->subDays(20), // expired
                ]
            );
        }

        // Documents linked to leases (via lease_id) for testing
        if (isset($leases['EV-1001'])) {
            Document::firstOrCreate(
                ['lease_id' => $leases['EV-1001']->id, 'type' => 'road_tax'],
                [
                    'name'        => 'Road Tax (Lease)',
                    'file_path'   => 'documents/dummy_lease1.pdf',
                    'expiry_date' => Carbon::now()->addMonths(6),
                ]
            );
        }
        if (isset($leases['EV-1002'])) {
            Document::firstOrCreate(
                ['lease_id' => $leases['EV-1002']->id, 'type' => 'insurance'],
                [
                    'name'        => 'Insurance (Lease)',
                    'file_path'   => 'documents/dummy_lease2.pdf',
                    'expiry_date' => Carbon::now()->addDays(45),
                ]
            );
        }

        // Driver license documents (linked to driver)
        $driverSarah = User::where('email', 'sarah.j@example.com')->first();
        if ($driverSarah) {
            Document::firstOrCreate(
                ['driver_id' => $driverSarah->id, 'type' => 'driving_license'],
                [
                    'name'        => "Driver's License",
                    'file_path'   => 'documents/dummy_license1.pdf',
                    'expiry_date' => Carbon::now()->addDays(30),
                ]
            );
        }

        $driverRobert = User::where('email', 'robert.chen@example.com')->first();
        if ($driverRobert) {
            Document::firstOrCreate(
                ['driver_id' => $driverRobert->id, 'type' => 'driving_license'],
                [
                    'name'        => "Driver's License",
                    'file_path'   => 'documents/dummy_license2.pdf',
                    'expiry_date' => Carbon::now()->addDays(30),
                ]
            );
        }

        $this->command->info('✅ EV Dashboard seeder completed successfully!');
    }
}