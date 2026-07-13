<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\UserController;
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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NurseryBatchController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\HorseRideController;
use App\Http\Controllers\PackhouseLotController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\QualityCheckController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SprayLogController;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Global quick-search
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Module 1: Master Data — readable by all roles (spec), writable by managers.
    // Write routes (create/store/edit/update/destroy) are registered before the
    // read routes so `create` is not swallowed by the `{model}` show route.
    // Farm map view (distinct path so it doesn't collide with farms/{farm}).
    Route::get('farms-map', [FarmController::class, 'map'])->name('farms.map');

    $masterData = ModuleAccess::middleware('master_data');
    foreach (['farms' => FarmController::class, 'blocks' => BlockController::class, 'crops' => CropController::class, 'assets' => AssetController::class] as $name => $ctrl) {
        Route::resource($name, $ctrl)->except(['index', 'show'])->middleware($masterData);
        Route::resource($name, $ctrl)->only(['index', 'show']);
    }

    // Module 2: Crop Planning & Seasonal Budgets — read-all, write restricted.
    $cropCycles = ModuleAccess::middleware('crop_cycles');
    Route::resource('crop-cycles', CropCycleController::class)->except(['index', 'show'])->middleware($cropCycles);
    Route::resource('crop-cycles', CropCycleController::class)->only(['index', 'show']);
    Route::middleware($cropCycles)->group(function () {
        Route::post('crop-cycles/{cropCycle}/activate', [CropCycleController::class, 'activate'])->name('crop-cycles.activate');
        Route::post('crop-cycles/{cropCycle}/complete', [CropCycleController::class, 'complete'])->name('crop-cycles.complete');
        Route::post('crop-cycles/{cropCycle}/cancel', [CropCycleController::class, 'cancel'])->name('crop-cycles.cancel');
        Route::post('crop-cycles/{cropCycle}/budget', [CropCycleController::class, 'setBudget'])->name('crop-cycles.budget');
    });

    // ---- Phase 2: Field Operations ----

    // Module 3: Nursery Management
    Route::middleware(ModuleAccess::middleware('nursery'))->group(function () {
        Route::resource('nursery-batches', NurseryBatchController::class);
        Route::post('nursery-batches/{nurseryBatch}/transplant', [NurseryBatchController::class, 'transplant'])->name('nursery-batches.transplant');
        Route::get('nursery-batches/{nurseryBatch}/plantings/{planting}/edit', [NurseryBatchController::class, 'editPlanting'])->name('nursery-batches.plantings.edit');
        Route::put('nursery-batches/{nurseryBatch}/plantings/{planting}', [NurseryBatchController::class, 'updatePlanting'])->name('nursery-batches.plantings.update');
        Route::delete('nursery-batches/{nurseryBatch}/plantings/{planting}', [NurseryBatchController::class, 'destroyPlanting'])->name('nursery-batches.plantings.destroy');
    });

    // Module 4: Daily Farm Operations
    Route::resource('daily-activities', DailyActivityController::class)->middleware(ModuleAccess::middleware('daily_ops'));

    // Module 5: Irrigation Management
    Route::resource('irrigation-logs', IrrigationLogController::class)->middleware(ModuleAccess::middleware('irrigation'));

    // Module 6: Fertigation & Nutrition
    Route::resource('fertigation-logs', FertigationLogController::class)->middleware(ModuleAccess::middleware('fertigation'));

    // Module 7: Pest & Disease Management
    Route::resource('spray-logs', SprayLogController::class)->middleware(ModuleAccess::middleware('pest'));

    // Module 8: Labour & Attendance
    Route::resource('labour-attendances', LabourAttendanceController::class)->middleware(ModuleAccess::middleware('labour'));

    // Module 8b: Projects, task-splitting & labour assignment
    Route::middleware(ModuleAccess::middleware('projects'))->group(function () {
        Route::resource('workers', WorkerController::class)->except('show');
        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
        Route::delete('projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');
        Route::post('projects/{project}/tasks/{task}/assignments', [ProjectController::class, 'storeAssignment'])->name('projects.assignments.store');
        Route::delete('projects/{project}/tasks/{task}/assignments/{assignment}', [ProjectController::class, 'destroyAssignment'])->name('projects.assignments.destroy');
        Route::post('projects/{project}/inputs', [ProjectController::class, 'storeInput'])->name('projects.inputs.store');
        Route::delete('projects/{project}/inputs/{transaction}', [ProjectController::class, 'destroyInput'])->name('projects.inputs.destroy');
    });

    // ---- Phase 3: Post-Harvest & Commercial ----

    // Module 9: Machinery & Fleet is covered by the Assets resource above.

    // Module 10: Inventory & Stores
    Route::middleware(ModuleAccess::middleware('inventory'))->group(function () {
        Route::resource('inventory-items', InventoryItemController::class);
        Route::post('inventory-items/{inventoryItem}/transactions', [InventoryItemController::class, 'storeTransaction'])->name('inventory-items.transactions.store');
    });

    // Module 11: Harvest Management
    Route::resource('harvest-batches', HarvestBatchController::class)->middleware(ModuleAccess::middleware('harvest'));

    // Module 12: Packhouse & Traceability
    Route::resource('packhouse-lots', PackhouseLotController::class)->middleware(ModuleAccess::middleware('packhouse'));
    Route::get('trace', [PackhouseLotController::class, 'trace'])->name('trace.lookup')->middleware(ModuleAccess::middleware('packhouse'));

    // Module 13: Quality Assurance
    Route::resource('quality-checks', QualityCheckController::class)->middleware(ModuleAccess::middleware('quality'));

    // Module 14: Sales & Customer Contracts
    Route::middleware(ModuleAccess::middleware('sales'))->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::resource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{salesOrder}/lines', [SalesOrderController::class, 'addLine'])->name('sales-orders.lines.store');
        Route::delete('sales-orders/{salesOrder}/lines/{line}', [SalesOrderController::class, 'destroyLine'])->name('sales-orders.lines.destroy');
    });

    // Module 15: Logistics & Dispatch
    Route::resource('dispatches', DispatchController::class)->middleware(ModuleAccess::middleware('logistics'));

    // Module 15b: Stables — horse rides & receipting
    Route::middleware(ModuleAccess::middleware('stables'))->group(function () {
        Route::resource('horses', HorseController::class)->except('show');
        Route::resource('guides', GuideController::class)->except('show');
        Route::resource('rides', HorseRideController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('rides/{ride}/assign', [HorseRideController::class, 'assign'])->name('rides.assign');
        Route::post('rides/{ride}/cancel', [HorseRideController::class, 'cancel'])->name('rides.cancel');
        Route::get('rides/{ride}/receipt', [HorseRideController::class, 'receipt'])->name('rides.receipt');
    });

    // Module 16: Finance (Native Ledger)
    Route::middleware(ModuleAccess::middleware('finance'))->group(function () {
        Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('finance/ledger', [FinanceController::class, 'ledger'])->name('finance.ledger');
        Route::post('finance/post', [FinanceController::class, 'post'])->name('finance.post');
    });

    // Module 17: Executive Dashboards & Analytics
    Route::middleware(ModuleAccess::middleware('analytics'))->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('analytics/recompute', [AnalyticsController::class, 'recompute'])->name('analytics.recompute');
    });

    // Notifications & Settings
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');

    // Administration: user onboarding, roles & the audit trail (owner + horticulture_manager).
    Route::middleware(ModuleAccess::middleware('admin'))->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/approve', [UserController::class, 'approveEmail'])->name('users.approve');
        Route::delete('users/approvals/{approvedEmail}', [UserController::class, 'revokeApproval'])->name('users.approvals.revoke');
        Route::put('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::put('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});
