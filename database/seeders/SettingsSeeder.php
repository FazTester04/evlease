<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            'interest_rate' => '5.00',       // 5% base interest
            'late_fee_rate' => '2.00',       // 2% per month overdue
            'late_fee_cap'  => '50.00',      // max 50% of payment
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}