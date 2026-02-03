<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Setting\Models\Setting;

class SettingController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Setting::class);
        $settings = \Modules\Setting\Models\Setting::all();
        return $this->successResponse([
            'settings' => $settings,
        ], 'Settings retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     * Accepts an array of settings to update in bulk.
     * 
     * Request format:
     * {
     *   "settings": [
     *     {"key": "app_name", "value": "New App Name"},
     *     {"key": "app_email", "value": "newemail@example.com"}
     *   ]
     * }
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        $updatedSettings = [];

        foreach ($request->settings as $settingData) {
            $setting = \Modules\Setting\Models\Setting::where('key', $settingData['key'])->first();

            if (!$setting) {
                continue; // Skip if setting key doesn't exist
            }

            $this->authorize('update', $setting);

            $setting->update([
                'value' => $settingData['value'],
            ]);

            $updatedSettings[] = $setting;
        }

        return $this->successResponse([
            'settings' => $updatedSettings,
        ], count($updatedSettings) . ' setting(s) updated successfully');
    }
}
