<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\OutletCashBookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        
        Route::get('/outlets', [OutletController::class, 'index']);
        Route::get('/outlets/{id}', [OutletController::class, 'show']);
        Route::post('/outlets', [OutletController::class, 'store']);
        Route::get('/outlets', [OutletController::class, 'index']);
        Route::put('/outlets/{id}', [OutletController::class, 'update']);
        Route::delete('/outlets/{id}', [OutletController::class, 'destroy']);
        Route::post('outlets/{id}/configuration', [OutletController::class, 'storeConfiguration']);
        Route::get('outlets/{id}/configuration', [OutletController::class, 'getConfiguration']);

        // CRUD Outlet Cash Books
        Route::get('outlets/{outletId}/cash-books', [OutletCashBookController::class, 'index']);
        Route::post('outlets/{outletId}/cash-books', [OutletCashBookController::class, 'store']);
        Route::put('outlets/{outletId}/cash-books/{id}', [OutletCashBookController::class, 'update']);
        Route::delete('outlets/{outletId}/cash-books/{id}', [OutletCashBookController::class, 'destroy']);
    });

});
