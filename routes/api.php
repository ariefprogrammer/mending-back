<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\OutletCashBookController;
use App\Http\Controllers\Api\V1\RevenueCategoryController;
use App\Http\Controllers\Api\V1\CostCategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\SatuanController;
use App\Http\Controllers\Api\V1\ServiceUnitController;
use App\Http\Controllers\Api\V1\OutletAssetController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\RevenueController;
use App\Http\Controllers\Api\V1\CostController;
use App\Http\Controllers\Api\V1\EmployeeRoleController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\EmployeeAttendanceController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\MaterialCategoryController;
use App\Http\Controllers\Api\V1\OutletMaterialController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use App\Http\Controllers\Api\V1\CashBookController;
use App\Http\Controllers\Api\V1\CashBookMappingController;
use App\Http\Controllers\Api\V1\TransactionItemProcessController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // wilayah - global
        Route::prefix('regions')->group(function () {
            Route::get('provinces', [RegionController::class, 'provinces']);
            Route::get('kabupatens', [RegionController::class, 'kabupatens']);
            Route::get('kecamatans', [RegionController::class, 'kecamatans']);
            Route::get('kelurahans', [RegionController::class, 'kelurahans']);
        });

        // Satuan - global
        Route::apiResource('units', UnitController::class)->only(['index', 'show']);
        Route::apiResource('satuans', SatuanController::class)->only(['index', 'show']);
        Route::apiResource('service-units', ServiceUnitController::class)->only(['index', 'show']);
        
        // outlets
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

        // Kategori Pendapatan
        Route::get('outlets/{outletId}/revenue-categories', [RevenueCategoryController::class, 'index']);
        Route::post('outlets/{outletId}/revenue-categories', [RevenueCategoryController::class, 'store']);
        Route::put('outlets/{outletId}/revenue-categories/{id}', [RevenueCategoryController::class, 'update']);
        Route::delete('outlets/{outletId}/revenue-categories/{id}', [RevenueCategoryController::class, 'destroy']);

        // Kategori Pengeluaran
        Route::get('outlets/{outletId}/cost-categories', [CostCategoryController::class, 'index']);
        Route::post('outlets/{outletId}/cost-categories', [CostCategoryController::class, 'store']);
        Route::put('outlets/{outletId}/cost-categories/{id}', [CostCategoryController::class, 'update']);
        Route::delete('outlets/{outletId}/cost-categories/{id}', [CostCategoryController::class, 'destroy']);

        // Customers
        Route::get('outlets/{outletId}/customers', [CustomerController::class, 'index']);
        Route::post('outlets/{outletId}/customers', [CustomerController::class, 'store']);
        Route::get('outlets/{outletId}/customers/{id}', [CustomerController::class, 'show']);
        Route::put('outlets/{outletId}/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('outlets/{outletId}/customers/{id}', [CustomerController::class, 'destroy']);

        Route::get('outlets/{outletId}/service-categories', [ServiceCategoryController::class, 'index']);
        Route::post('outlets/{outletId}/service-categories', [ServiceCategoryController::class, 'store']);
        Route::put('outlets/{outletId}/service-categories/{id}', [ServiceCategoryController::class, 'update']);
        Route::delete('outlets/{outletId}/service-categories/{id}', [ServiceCategoryController::class, 'destroy']);

        // Layanan
        Route::get('outlets/{outletId}/services', [ServiceController::class, 'index']);
        Route::post('outlets/{outletId}/services', [ServiceController::class, 'store']);
        Route::put('outlets/{outletId}/services/{id}', [ServiceController::class, 'update']);
        Route::delete('outlets/{outletId}/services/{id}', [ServiceController::class, 'destroy']);

        // Asset Outlet
        Route::get('outlets/{outletId}/assets', [OutletAssetController::class, 'index']);
        Route::post('outlets/{outletId}/assets', [OutletAssetController::class, 'store']);
        Route::put('outlets/{outletId}/assets/{id}', [OutletAssetController::class, 'update']);
        Route::delete('outlets/{outletId}/assets/{id}', [OutletAssetController::class, 'destroy']);

        Route::prefix('outlets/{outletId}/units')->group(function () {
            Route::get('/',          [UnitController::class, 'index']);
            Route::post('/',         [UnitController::class, 'store']);
            Route::get('/{id}',      [UnitController::class, 'show']);
            Route::put('/{id}',      [UnitController::class, 'update']);
            Route::delete('/{id}',   [UnitController::class, 'destroy']);
        });

        // payment methods
        Route::prefix('outlets/{outletId}/payment-methods')->group(function () {
            Route::get('/',         [PaymentMethodController::class, 'index']);
            Route::post('/',        [PaymentMethodController::class, 'store']);
            Route::put('/{id}',     [PaymentMethodController::class, 'update']);
            Route::delete('/{id}',  [PaymentMethodController::class, 'destroy']);
        });

        // revenue
        Route::prefix('outlets/{outletId}/revenues')->group(function () {
            Route::get('/',        [RevenueController::class, 'index']);
            Route::post('/',       [RevenueController::class, 'store']);
            Route::get('/{id}',    [RevenueController::class, 'show']);
            Route::put('/{id}',    [RevenueController::class, 'update']);
            Route::delete('/{id}', [RevenueController::class, 'destroy']);
        });

        // cost
        Route::prefix('outlets/{outletId}/costs')->group(function () {
            Route::get('/',        [CostController::class, 'index']);
            Route::post('/',       [CostController::class, 'store']);
            Route::get('/{id}',    [CostController::class, 'show']);
            Route::put('/{id}',    [CostController::class, 'update']);
            Route::delete('/{id}', [CostController::class, 'destroy']);
        });

        // Employee Roles
        Route::prefix('outlets/{outletId}/employee-roles')->group(function () {
            Route::get('/',        [EmployeeRoleController::class, 'index']);
            Route::post('/',       [EmployeeRoleController::class, 'store']);
            Route::get('/{id}',    [EmployeeRoleController::class, 'show']);
            Route::put('/{id}',    [EmployeeRoleController::class, 'update']);
            Route::delete('/{id}', [EmployeeRoleController::class, 'destroy']);
        });

        // Employees
        Route::prefix('outlets/{outletId}/employees')->group(function () {
            Route::get('/',        [EmployeeController::class, 'index']);
            Route::post('/',       [EmployeeController::class, 'store']);
            Route::get('/{id}',    [EmployeeController::class, 'show']);
            Route::post('/{id}',   [EmployeeController::class, 'update']); // POST bukan PUT karena multipart/form-data
            Route::delete('/{id}', [EmployeeController::class, 'destroy']);
            Route::post('/{id}/activate', [EmployeeController::class, 'activate']);
            Route::post('/{id}/deactivate', [EmployeeController::class, 'deactivate']);
        });

        // Permissions - global
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/permissions/{id}', [PermissionController::class, 'show']);
        Route::get('/permissions/module/filter', [PermissionController::class, 'byModule']);

        // Employee Attendance
        Route::prefix('outlets/{outletId}')->group(function () {
        
            Route::post('attendances/check-in', [EmployeeAttendanceController::class, 'checkIn']);
            Route::post('attendances/{id}/overtime', [EmployeeAttendanceController::class, 'overtime']);
            Route::post('attendances/{id}/check-out', [EmployeeAttendanceController::class, 'checkOut']);
            Route::get('attendances', [EmployeeAttendanceController::class, 'index']);
            Route::post('attendances', [EmployeeAttendanceController::class, 'store']);
            Route::get('attendances/{id}', [EmployeeAttendanceController::class, 'show']);
            Route::put('attendances/{id}', [EmployeeAttendanceController::class, 'update']);
            Route::delete('attendances/{id}', [EmployeeAttendanceController::class, 'destroy']);
            
            // Presensi per employee
            Route::prefix('employees/{employeeId}')->group(function () {
                Route::get('attendances', [EmployeeAttendanceController::class, 'myAttendance']);
                Route::get('attendances/today', [EmployeeAttendanceController::class, 'todayAttendance']);
            });
        });

        // Leave Requests
        Route::prefix('outlets/{outletId}/leave-requests')->group(function () {
            Route::get('/',        [LeaveRequestController::class, 'index']);
            Route::post('/',       [LeaveRequestController::class, 'store']);
            Route::get('/all',     [LeaveRequestController::class, 'allLeaveRequests']);
            Route::get('/{id}',    [LeaveRequestController::class, 'show']);
            Route::put('/{id}',    [LeaveRequestController::class, 'update']);
            Route::patch('/{id}/review', [LeaveRequestController::class, 'reviewLeaveRequest']);
            Route::delete('/{id}', [LeaveRequestController::class, 'destroy']);
            Route::get('/employee/{employeeId}/my',      [LeaveRequestController::class, 'myLeaveRequest']);
        });

        // Material Categories
        Route::prefix('outlets/{outletId}/material-categories')->group(function () {
            Route::get('/',        [MaterialCategoryController::class, 'index']);
            Route::post('/',       [MaterialCategoryController::class, 'store']);
            Route::put('/{id}',    [MaterialCategoryController::class, 'update']);
            Route::delete('/{id}', [MaterialCategoryController::class, 'destroy']);
        });

        // Materials
        Route::prefix('outlets/{outletId}/materials')->group(function () {
            Route::get('/',        [OutletMaterialController::class, 'index']);
            Route::get('/{id}',    [OutletMaterialController::class, 'show']);
            Route::post('/',       [OutletMaterialController::class, 'store']);
            Route::put('/{id}',    [OutletMaterialController::class, 'update']);
            Route::delete('/{id}', [OutletMaterialController::class, 'destroy']);
        });

        Route::prefix('outlets/{outletId}/stock-opnames')->group(function () {
            Route::get('/',        [StockOpnameController::class, 'index']);
            Route::get('/{id}',    [StockOpnameController::class, 'show']);
            Route::post('/',       [StockOpnameController::class, 'store']);
            Route::put('/{id}/items',    [StockOpnameController::class, 'updateItems']);
            Route::delete('/{id}', [StockOpnameController::class, 'destroy']);
        });

        // Cash Books + Transactions
        Route::get('outlets/{outletId}/cash-books', [CashBookController::class, 'index']);
        Route::get('outlets/{outletId}/cash-books/{cashBookId}', [CashBookController::class, 'show']);

        Route::prefix('outlets/{outletId}/transactions')->group(function () {
            Route::get('/',        [TransactionController::class, 'index']);
            Route::post('/',       [TransactionController::class, 'store']);
            Route::get('/{id}',    [TransactionController::class, 'show']);
            Route::put('/{id}',    [TransactionController::class, 'update']);
            Route::delete('/{id}', [TransactionController::class, 'destroy']);
            Route::post('/{id}/pay',[TransactionController::class, 'pay']);
        });

        // Cash Book Mappings
        Route::prefix('outlets/{outletId}/cash-book-mappings')->group(function () {
            Route::get('/',        [CashBookMappingController::class, 'index']);
            Route::post('/',       [CashBookMappingController::class, 'store']);
            Route::put('/{id}',    [CashBookMappingController::class, 'update']);
            Route::delete('/{id}', [CashBookMappingController::class, 'destroy']);
        });

        // ─── Item Processes ───────────────────────────────────────────────────
        Route::prefix('outlets/{outletId}/transactions/{transactionId}/processes')->group(function () {
            Route::post('/',                [TransactionItemProcessController::class, 'store']);
            Route::put('/{serviceFlowId}',  [TransactionItemProcessController::class, 'update']);
        });
    });

});
