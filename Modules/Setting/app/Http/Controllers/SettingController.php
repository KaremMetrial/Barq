<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
        $setting->update($request->only(['value', 'type']));
        return $this->successResponse([
            'setting' => $setting,
        ], 'Setting updated successfully');
    }

}
