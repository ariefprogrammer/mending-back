<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    public function general()
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'admin_whatsapp_number' => Setting::get('admin_whatsapp_number', '628000000000'),
            ]
        ]);
    }
}