<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceUnit;

class ServiceUnitController extends Controller
{
    // LIST SEMUA SERVICE UNIT
    public function index()
    {
        $serviceUnits = ServiceUnit::orderBy('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $serviceUnits
        ]);
    }

    // DETAIL SERVICE UNIT
    public function show($id)
    {
        $serviceUnit = ServiceUnit::find($id);
        if (!$serviceUnit) return response()->json(['message' => 'Service unit tidak ditemukan'], 404);

        return response()->json([
            'status' => 'success',
            'data' => $serviceUnit
        ]);
    }
}