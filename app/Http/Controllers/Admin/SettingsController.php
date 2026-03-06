<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show the late fee settings form.
     */
    public function lateFee()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return Inertia::render('Settings/LateFee', [
            'late_fee_rate' => $settings['late_fee_rate'] ?? '2.00',
            'late_fee_cap'  => $settings['late_fee_cap'] ?? '50.00',
        ]);
    }

    /**
     * Update late fee settings.
     */
    public function updateLateFee(Request $request)
    {
        $request->validate([
            'late_fee_rate' => 'required|numeric|min:0|max:100',
            'late_fee_cap'  => 'required|numeric|min:0|max:100',
        ]);

        Setting::updateOrCreate(['key' => 'late_fee_rate'], ['value' => $request->late_fee_rate]);
        Setting::updateOrCreate(['key' => 'late_fee_cap'], ['value' => $request->late_fee_cap]);

        return redirect()->back()->with('success', 'Late fee settings updated.');
    }
}