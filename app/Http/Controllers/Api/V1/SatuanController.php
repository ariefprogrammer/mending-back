<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Satuan;

class SatuanController extends Controller
{
    // LIST SEMUA SATUAN
    public function index()
    {
        $satuans = Satuan::orderBy('type')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $satuans
        ]);
    }

    // DETAIL SATUAN
    public function show($id)
    {
        $satuan = Satuan::find($id);
        if (!$satuan) return response()->json(['message' => 'Satuan tidak ditemukan'], 404);

        return response()->json([
            'status' => 'success',
            'data' => $satuan
        ]);
    }
}