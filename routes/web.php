<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\CropCycleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FertigationLogController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HarvestBatchController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\IrrigationLogController;
use App\Http\Controllers\LabourAttendanceController;
use App\Http\Controllers\NurseryBatchController;
use App\Http\Controllers\PackhouseLotController;
use App\Http\Controllers\QualityCheckController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SprayLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Module 1: Master Data
    Route::resource('farms', FarmController::class);
    Route::resource('blocks', BlockController::class);
    Route::resource('crops', CropController::class);
    Route::resource('assets', AssetController::class);

    // Module 2: Crop Planning & Seasonal Budgets
    Route::resource('crop-cycles', CropCycleController::class);
    Route::post('crop-cycles/{cropCycle}/activate', [CropCycleController::class, 'activate'])->name('crop-cycles.activate');
    Route::post('crop-cycles/{cropCycle}/complete', [CropCycleController::class, 'complete'])->name('crop-cycles.complete');
    Route::post('crop-cycles/{cropCycle}/cancel', [CropCycleController::class, 'cancel'])->name('crop-cycles.cancel');
    Route::post('crop-cycles/{cropCycle}/budget', [CropCycleController::class, 'setBudget'])->name('crop-cycles.budget');

    // ---- Phase 2: Field Operations ----

    // Module 3: Nursery Management
    Route::resource('nursery-batches', NurseryBatchController::class);
    Route::post('nursery-batches/{nurseryBatch}/transplant', [NurseryBatchController::class, 'transplant'])->name('nursery-batches.transplant');

    // Module 4: Daily Farm Operations
    Route::resource('daily-activities', DailyActivityController::class);

    // Module 5: Irrigation Management
    Route::resource('irrigation-logs', IrrigationLogController::class);

    // Module 6: Fertigation & Nutrition
    Route::resource('fertigation-logs', FertigationLogController::class);

    // Module 7: Pest & Disease Management
    Route::resource('spray-logs', SprayLogController::class);

    // Module 8: Labour & Attendance
    Route::resource('labour-attendances', LabourAttendanceController::class);

    // ---- Phase 3: Post-Harvest & Commercial ----

    // Module 9: Machinery & Fleet is covered by the Assets resource above.

    // Module 10: Inventory & Stores
    Route::resource('inventory-items', InventoryItemController::class);
    Route::post('inventory-items/{inventoryItem}/transactions', [InventoryItemController::class, 'storeTransaction'])->name('inventory-items.transactions.store');

    // Module 11: Harvest Management
    Route::resource('harvest-batches', HarvestBatchController::class);

    // Module 12: Packhouse & Traceability
    Route::resource('packhouse-lots', PackhouseLotController::class);

    // Module 13: Quality Assurance
    Route::resource('quality-checks', QualityCheckController::class);

    // Module 14: Sales & Customer Contracts
    Route::resource('customers', CustomerController::class);
    Route::resource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/lines', [SalesOrderController::class, 'addLine'])->name('sales-orders.lines.store');
    Route::delete('sales-orders/{salesOrder}/lines/{line}', [SalesOrderController::class, 'destroyLine'])->name('sales-orders.lines.destroy');

    // Module 15: Logistics & Dispatch
    Route::resource('dispatches', DispatchController::class);

    // Module 16: Finance (Native Ledger)
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/ledger', [FinanceController::class, 'ledger'])->name('finance.ledger');
    Route::post('finance/post', [FinanceController::class, 'post'])->name('finance.post');

    // Module 17: Executive Dashboards & Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('analytics/recompute', [AnalyticsController::class, 'recompute'])->name('analytics.recompute');
});
