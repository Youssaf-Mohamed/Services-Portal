<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportSetting;
use Illuminate\Http\Request;

class TransportSettingController extends Controller
{
    public function show()
    {
        $settings = TransportSetting::first();
        
        if (!$settings) {
            // Create default settings if not exists
            $settings = TransportSetting::create([
                'days_per_week' => 5,
                'weeks_in_month' => 4,
                'weeks_in_term' => 12,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $settings = TransportSetting::first();
        
        if (!$settings) {
            $settings = new TransportSetting();
        }

        $validated = $request->validate([
            'days_per_week' => 'required|integer|min:1|max:7',
            'weeks_in_month' => 'required|integer|min:1|max:5',
            'weeks_in_term' => 'required|integer|min:1|max:20',
        ]);

        $validated['updated_by'] = auth()->id();

        if ($settings->exists) {
            $settings->update($validated);
        } else {
            $settings->fill($validated);
            $settings->save();
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'Settings updated successfully'
        ]);
    }
}
