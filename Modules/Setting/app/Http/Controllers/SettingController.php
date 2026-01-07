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
     */
    public function update(Request $request, $id) {
        $setting = \Modules\Setting\Models\Setting::findOrFail($id);
        $this->authorize('update', $setting);
        $setting->update($request->only(['value', 'type']));
        return $this->successResponse([
            'setting' => $setting,
        ], 'Setting updated successfully');
    }

}
