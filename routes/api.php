<?php

use App\Http\Controllers\Admin\FsComCatalogController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Asset\GbicController;
use App\Http\Controllers\Asset\NetworkSwitchController;
use App\Http\Controllers\Asset\PatchCordController;
use App\Http\Controllers\Asset\SwitchModelController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Catalog\FsComProductController;
use App\Http\Controllers\Inventory\AssignmentController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Location\BayController;
use App\Http\Controllers\Location\RackController;
use App\Http\Controllers\Location\SiteController;
use App\Http\Controllers\Report\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
    });

    // Admin - User Management
    Route::prefix('admin/users')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/', [UserApprovalController::class, 'index']);
        Route::get('/pending', [UserApprovalController::class, 'pending']);
        Route::post('/{user}/approve', [UserApprovalController::class, 'approve']);
        Route::post('/{user}/reject', [UserApprovalController::class, 'reject']);
        Route::put('/{user}/role', [UserApprovalController::class, 'updateRole']);
        Route::delete('/{user}', [UserApprovalController::class, 'destroy']);
    });

    // Admin - FS.com Catalog Management (CSV Import/Export)
    Route::prefix('admin/fscom-catalog')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/', [FsComCatalogController::class, 'index']);
        Route::get('/stats', [FsComCatalogController::class, 'stats']);
        Route::post('/template', [FsComCatalogController::class, 'generateTemplate']);
        Route::post('/validate', [FsComCatalogController::class, 'validateCsv']);
        Route::post('/import', [FsComCatalogController::class, 'import']);
        Route::post('/export', [FsComCatalogController::class, 'export']);
        Route::delete('/{product}', [FsComCatalogController::class, 'destroy']);
        Route::post('/bulk-delete', [FsComCatalogController::class, 'bulkDelete']);
    });

    // Sites
    Route::apiResource('sites', SiteController::class);

    // Racks
    Route::apiResource('racks', RackController::class);

    // Bays
    Route::apiResource('bays', BayController::class);

    // Switch Models
    Route::apiResource('switch-models', SwitchModelController::class);

    // Network Switches
    Route::apiResource('switches', NetworkSwitchController::class);
    Route::get('switches/{switch}/ports', [NetworkSwitchController::class, 'ports']);
    Route::get('switches/barcode/{barcode}', [NetworkSwitchController::class, 'findByBarcode']);

    // GBICs
    Route::apiResource('gbics', GbicController::class);
    Route::get('gbics/{gbic}/history', [GbicController::class, 'history']);
    Route::get('gbics/barcode/{barcode}', [GbicController::class, 'findByBarcode']);
    Route::post('gbics/bulk-import', [GbicController::class, 'bulkImport']);

    // Patch Cords
    Route::apiResource('patch-cords', PatchCordController::class);
    Route::get('patch-cords/barcode/{barcode}', [PatchCordController::class, 'findByBarcode']);

    // Assignments
    Route::apiResource('assignments', AssignmentController::class);
    Route::get('assignments/switch/{switch}', [AssignmentController::class, 'bySwitch']);
    Route::get('assignments/history', [AssignmentController::class, 'history']);

    // Stock Movements
    Route::apiResource('stock-movements', StockMovementController::class);
    Route::get('stock-movements/asset/{type}/{id}', [StockMovementController::class, 'byAsset']);
    Route::get('stock-movements/statistics', [StockMovementController::class, 'statistics']);

    // FS.com Products
    Route::prefix('catalog/fscom')->group(function () {
        Route::get('/', [FsComProductController::class, 'index']);
        Route::get('/categories', [FsComProductController::class, 'categories']);
        Route::get('/search', [FsComProductController::class, 'search']);
        Route::get('/stats', [FsComProductController::class, 'stats']);
        Route::post('/sync', [FsComProductController::class, 'sync'])->middleware('role:super_admin|admin');
        Route::get('/{fsComProduct}', [FsComProductController::class, 'show']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
        Route::get('/stock-overview', [DashboardController::class, 'stockOverview']);
        Route::get('/alerts-summary', [DashboardController::class, 'alertsSummary']);
    });
});
