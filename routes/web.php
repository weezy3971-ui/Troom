<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiReportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetCheckoutController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\CropCycleController;
use App\Http\Controllers\CropMonitoringController;
use App\Http\Controllers\CropProgramController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FertigationLogController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HarvestBatchController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\HorseRideController;
use App\Http\Controllers\InformationSourceController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\IrrigationLogController;
use App\Http\Controllers\LabourAttendanceController;
use App\Http\Controllers\LandPreparationController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NurseryBatchController;
use App\Http\Controllers\OutgrowerController;
use App\Http\Controllers\PackhouseLotController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QualityCheckController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SprayLogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WeighScaleReadingController;
use App\Http\Controllers\WhatsappOpsController;
use App\Http\Controllers\WorkerController;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::get('/register/verify', [AuthController::class, 'showVerify'])->name('register.verify');
Route::post('/register/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('register.verify.submit');
Route::post('/register/verify/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1')->name('register.verify.resend');

// Self-service password reset (SMS OTP)
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetOtp'])->middleware('throttle:5,1')->name('password.email');
Route::get('/forgot-password/verify', [AuthController::class, 'showResetVerify'])->name('password.reset.verify');
Route::post('/forgot-password/verify', [AuthController::class, 'resetWithOtp'])->middleware('throttle:10,1')->name('password.update');
Route::post('/forgot-password/verify/resend', [AuthController::class, 'resendResetOtp'])->middleware('throttle:3,1')->name('password.reset.resend');

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

    // Expenses — open to every role; anyone in the field can log ad-hoc spend
    // (tools, fuel, fines, casual labour paid in cash, etc).
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/voucher', [ExpenseController::class, 'issueVoucher'])->name('expenses.issue-voucher');
    Route::get('expenses/{expense}/voucher', [ExpenseController::class, 'voucher'])->name('expenses.voucher');

    // Module 1: Master Data — readable by all roles (spec), writable by managers.
    // Write routes (create/store/edit/update/destroy) are registered before the
    // read routes so `create` is not swallowed by the `{model}` show route.
    // Farm map view (distinct path so it doesn't collide with farms/{farm}).
    Route::get('farms-map', [FarmController::class, 'map'])->name('farms.map');

    $masterData = ModuleAccess::middleware('master_data');

    // The single "New Crop Cycle" flow: one page that picks or creates the farm,
    // block and crop and sets the budget. It writes master data as a byproduct,
    // but it is the crop-cycle create screen — gated behind crop_cycles (a
    // superset of master_data, so no former user loses access).
    $cycleWrite = ModuleAccess::middleware('crop_cycles');
    Route::get('setup', [SetupController::class, 'index'])->name('setup')->middleware($cycleWrite);
    Route::post('setup', [SetupController::class, 'store'])->middleware($cycleWrite);

    foreach (['farms' => FarmController::class, 'blocks' => BlockController::class, 'crops' => CropController::class, 'assets' => AssetController::class] as $name => $ctrl) {
        Route::resource($name, $ctrl)->except(['index', 'show'])->middleware($masterData);
        Route::resource($name, $ctrl)->only(['index', 'show']);
    }

    // Land preparation — the step between adding a block and planting into it.
    // Read-all like crop cycles; the field work itself is a daily-ops write.
    Route::get('blocks/{block}/land-preparation', [LandPreparationController::class, 'open'])->name('land-preparations.open');
    Route::get('land-preparations/{landPreparation}', [LandPreparationController::class, 'show'])->name('land-preparations.show');
    Route::middleware(ModuleAccess::middleware('daily_ops'))->group(function () {
        Route::post('blocks/{block}/land-preparation', [LandPreparationController::class, 'store'])->name('land-preparations.store');
        Route::put('land-preparations/{landPreparation}', [LandPreparationController::class, 'update'])->name('land-preparations.update');
        Route::put('land-preparations/{landPreparation}/waive', [LandPreparationController::class, 'waive'])->name('land-preparations.waive');
        Route::put('land-preparation-tasks/{task}', [LandPreparationController::class, 'updateTask'])->name('land-preparations.tasks.update');
        Route::delete('land-preparations/{landPreparation}', [LandPreparationController::class, 'destroy'])->name('land-preparations.destroy');
    });

    // Module 2: Crop Planning & Seasonal Budgets — read-all, write restricted.
    $cropCycles = ModuleAccess::middleware('crop_cycles');
    // Creating a cycle happens in the merged "New Crop Cycle" flow (setup), which
    // can also spin up the farm/block/crop it needs. The bare create form is
    // retired; its route redirects so existing links still land somewhere sensible.
    Route::get('crop-cycles/create', fn () => redirect()->route('setup'))
        ->name('crop-cycles.create')->middleware($cropCycles);
    Route::resource('crop-cycles', CropCycleController::class)->except(['index', 'show', 'create'])->middleware($cropCycles);
    // Planting schedule planner — a self-contained tool; must be declared before the
    // {cropCycle} show route so "planner" isn't parsed as a crop-cycle id.
    Route::get('crop-cycles/planner', [CropCycleController::class, 'planner'])->name('crop-cycles.planner');
    Route::resource('crop-cycles', CropCycleController::class)->only(['index', 'show']);
    Route::middleware($cropCycles)->group(function () {
        Route::post('crop-cycles/{cropCycle}/activate', [CropCycleController::class, 'activate'])->name('crop-cycles.activate');
        Route::post('crop-cycles/{cropCycle}/complete', [CropCycleController::class, 'complete'])->name('crop-cycles.complete');
        Route::post('crop-cycles/{cropCycle}/cancel', [CropCycleController::class, 'cancel'])->name('crop-cycles.cancel');
        Route::post('crop-cycles/{cropCycle}/budget', [CropCycleController::class, 'setBudget'])->name('crop-cycles.budget');

        // Crop stage programs (reusable per-crop protocols)
        Route::resource('crop-programs', CropProgramController::class);
        Route::post('crop-programs/{cropProgram}/stages', [CropProgramController::class, 'storeStage'])->name('crop-programs.stages.store');
        Route::delete('crop-programs/{cropProgram}/stages/{stage}', [CropProgramController::class, 'destroyStage'])->name('crop-programs.stages.destroy');

        // In-season crop monitoring — germination checks, stand counts, pre-harvest sampling
        Route::post('crop-cycles/{cropCycle}/germination', [CropMonitoringController::class, 'storeGermination'])->name('crop-cycles.germination.store');
        Route::delete('crop-cycles/{cropCycle}/germination/{germinationCheck}', [CropMonitoringController::class, 'destroyGermination'])->name('crop-cycles.germination.destroy');
        Route::post('crop-cycles/{cropCycle}/population', [CropMonitoringController::class, 'storePopulation'])->name('crop-cycles.population.store');
        Route::delete('crop-cycles/{cropCycle}/population/{plantPopulationCount}', [CropMonitoringController::class, 'destroyPopulation'])->name('crop-cycles.population.destroy');
        Route::post('crop-cycles/{cropCycle}/forecast', [CropMonitoringController::class, 'storeForecast'])->name('crop-cycles.forecast.store');
        Route::delete('crop-cycles/{cropCycle}/forecast/{yieldForecast}', [CropMonitoringController::class, 'destroyForecast'])->name('crop-cycles.forecast.destroy');
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

    Route::middleware(ModuleAccess::middleware('whatsapp_ops'))->prefix('whatsapp-ops')->name('whatsapp-ops.')->group(function () {
        Route::get('/', [WhatsappOpsController::class, 'index'])->name('index');
        Route::post('/simulate', [WhatsappOpsController::class, 'simulate'])->name('simulate');
        Route::put('/{whatsappMessage}/interpretation', [WhatsappOpsController::class, 'updateInterpretation'])->name('interpretation.update');
        Route::post('/{whatsappMessage}/approve', [WhatsappOpsController::class, 'approve'])->name('approve');
        Route::post('/{whatsappMessage}/reject', [WhatsappOpsController::class, 'reject'])->name('reject');
        Route::post('/{whatsappMessage}/post', [WhatsappOpsController::class, 'post'])->name('post');
    });

    // Module 5: Irrigation Management
    Route::resource('irrigation-logs', IrrigationLogController::class)->middleware(ModuleAccess::middleware('irrigation'));

    // Module 6: Fertigation & Nutrition
    Route::resource('fertigation-logs', FertigationLogController::class)->middleware(ModuleAccess::middleware('fertigation'));

    // Module 7: Pest & Disease Management
    Route::resource('spray-logs', SprayLogController::class)->middleware(ModuleAccess::middleware('pest'));

    // Module 8: Labour & Attendance (hourly time via optional check-in/out, or target/piece-rate)
    Route::resource('labour-attendances', LabourAttendanceController::class)->middleware(ModuleAccess::middleware('labour'));

    // Module 8c: Weigh scale notifications (digital scale feed — who weighed what)
    Route::middleware(ModuleAccess::middleware('weighing'))->group(function () {
        Route::get('weigh-scale-readings', [WeighScaleReadingController::class, 'index'])->name('weigh-scale-readings.index');
        Route::get('weigh-scale-readings/create', [WeighScaleReadingController::class, 'create'])->name('weigh-scale-readings.create');
        Route::post('weigh-scale-readings', [WeighScaleReadingController::class, 'store'])->name('weigh-scale-readings.store');
        Route::post('weigh-scale-readings/{weighScaleReading}/acknowledge', [WeighScaleReadingController::class, 'acknowledge'])->name('weigh-scale-readings.acknowledge');
        Route::delete('weigh-scale-readings/{weighScaleReading}', [WeighScaleReadingController::class, 'destroy'])->name('weigh-scale-readings.destroy');
    });

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

    // Module 9b: Asset check-out / check-in register (tool custody)
    Route::middleware(ModuleAccess::middleware('checkouts'))->group(function () {
        Route::get('checkouts', [AssetCheckoutController::class, 'index'])->name('checkouts.index');
        Route::get('checkouts/create', [AssetCheckoutController::class, 'create'])->name('checkouts.create');
        Route::post('checkouts', [AssetCheckoutController::class, 'store'])->name('checkouts.store');
        Route::post('checkouts/{checkout}/checkin', [AssetCheckoutController::class, 'checkin'])->name('checkouts.checkin');
        Route::delete('checkouts/{checkout}', [AssetCheckoutController::class, 'destroy'])->name('checkouts.destroy');
    });

    // Module 10: Inventory & Stores
    Route::middleware(ModuleAccess::middleware('inventory'))->group(function () {
        Route::resource('inventory-items', InventoryItemController::class);
        Route::post('inventory-items/{inventoryItem}/transactions', [InventoryItemController::class, 'storeTransaction'])->name('inventory-items.transactions.store');

        // Module 10b: Procurement requests (requested → ordered → received)
        Route::resource('procurement-requests', ProcurementRequestController::class)->except(['edit', 'update']);
        Route::post('procurement-requests/{procurementRequest}/lines', [ProcurementRequestController::class, 'storeLine'])->name('procurement-requests.lines.store');
        Route::delete('procurement-requests/{procurementRequest}/lines/{line}', [ProcurementRequestController::class, 'destroyLine'])->name('procurement-requests.lines.destroy');
        Route::post('procurement-requests/{procurementRequest}/order', [ProcurementRequestController::class, 'markOrdered'])->name('procurement-requests.order');
        Route::post('procurement-requests/{procurementRequest}/receive', [ProcurementRequestController::class, 'markReceived'])->name('procurement-requests.receive');
    });

    // Module 11: Harvest Management
    Route::middleware(ModuleAccess::middleware('harvest'))->group(function () {
        Route::resource('harvest-batches', HarvestBatchController::class);
        Route::post('harvest-batches/{harvestBatch}/confirm', [HarvestBatchController::class, 'confirm'])->name('harvest-batches.confirm');
        Route::post('harvest-batches/{harvestBatch}/by-products', [HarvestBatchController::class, 'storeByProduct'])->name('harvest-batches.by-products.store');
        Route::delete('harvest-batches/{harvestBatch}/by-products/{byProduct}', [HarvestBatchController::class, 'destroyByProduct'])->name('harvest-batches.by-products.destroy');
    });

    // Module 12: Packhouse & Traceability
    Route::resource('packhouse-lots', PackhouseLotController::class)->middleware(ModuleAccess::middleware('packhouse'));
    Route::get('trace', [PackhouseLotController::class, 'trace'])->name('trace.lookup')->middleware(ModuleAccess::middleware('packhouse'));

    // Module 13: Quality Assurance
    Route::resource('quality-checks', QualityCheckController::class)->middleware(ModuleAccess::middleware('quality'));

    // Module 14: Sales & Customer Contracts
    Route::middleware(ModuleAccess::middleware('sales'))->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::resource('outgrowers', OutgrowerController::class);
        Route::resource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{salesOrder}/lines', [SalesOrderController::class, 'addLine'])->name('sales-orders.lines.store');
        Route::delete('sales-orders/{salesOrder}/lines/{line}', [SalesOrderController::class, 'destroyLine'])->name('sales-orders.lines.destroy');
        Route::post('sales-orders/{salesOrder}/delivery', [SalesOrderController::class, 'recordDelivery'])->name('sales-orders.delivery');
        Route::post('sales-orders/{salesOrder}/invoice', [SalesOrderController::class, 'issueInvoice'])->name('sales-orders.invoice');

        // Module 14b: Payments received & receipting. Recording a payment sits
        // with sales (whoever takes the money issues the receipt); voiding one
        // is a finance correction and is gated separately below.
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('sales-orders/{salesOrder}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

        // C2B demo: stands in for a customer paying at the paybill with no
        // app involved. Sits with sales, same as recording a manual payment.
        Route::get('mpesa/simulate-c2b', [MpesaController::class, 'simulateC2BForm'])->name('mpesa.simulate-c2b.form');
        Route::post('mpesa/simulate-c2b', [MpesaController::class, 'simulateC2B'])->name('mpesa.simulate-c2b');
    });

    Route::post('payments/{payment}/void', [PaymentController::class, 'void'])
        ->middleware(ModuleAccess::middleware('finance'))
        ->name('payments.void');

    // M-Pesa — B2C disbursement and the reconciliation log. Disbursing real
    // money is a finance action, same standard as voiding a receipt.
    Route::middleware(ModuleAccess::middleware('finance'))->group(function () {
        Route::get('mpesa', [MpesaController::class, 'index'])->name('mpesa.index');
        Route::post('expenses/{expense}/disburse', [MpesaController::class, 'disburse'])->name('expenses.disburse');
    });

    // Vendors — who the farm pays. Kept under finance rather than procurement
    // because their phone number is a payment destination, not a contact.
    Route::resource('vendors', VendorController::class)
        ->except('show')
        ->middleware(ModuleAccess::middleware('finance'));

    // Module 15: Logistics & Dispatch
    Route::resource('dispatches', DispatchController::class)->middleware(ModuleAccess::middleware('logistics'));

    // Module 15b: Stables — horse rides & receipting
    Route::middleware(ModuleAccess::middleware('stables'))->group(function () {
        Route::resource('horses', HorseController::class)->except('show');
        Route::resource('guides', GuideController::class)->except('show');
        Route::resource('rides', HorseRideController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
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

    // Module 18: AI-generated reports
    Route::middleware(ModuleAccess::middleware('ai'))->group(function () {
        Route::get('ai-reports', [AiReportController::class, 'index'])->name('ai-reports.index');
        Route::get('ai-reports/create', [AiReportController::class, 'create'])->name('ai-reports.create');
        Route::post('ai-reports', [AiReportController::class, 'store'])->name('ai-reports.store');
        Route::get('ai-reports/{aiReport}', [AiReportController::class, 'show'])->name('ai-reports.show');
        Route::post('ai-reports/{aiReport}/regenerate', [AiReportController::class, 'regenerate'])->name('ai-reports.regenerate');
        Route::delete('ai-reports/{aiReport}', [AiReportController::class, 'destroy'])->name('ai-reports.destroy');
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
        Route::put('users/approvals/{approvedEmail}/phone', [UserController::class, 'updateApprovalPhone'])->name('users.approvals.phone');
        Route::put('users/password', [UserController::class, 'updatePassword'])->name('users.password');
        Route::put('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::put('users/{user}/phone', [UserController::class, 'updateUserPhone'])->name('users.phone');
        Route::put('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/export', [ActivityLogController::class, 'exportPdf'])->name('activity-logs.export');

        // Sources: every external site we take crop information from. The list
        // syncs itself from the code; admins open a source or remove it.
        Route::get('information-sources', [InformationSourceController::class, 'index'])->name('information-sources.index');
        Route::post('information-sources', [InformationSourceController::class, 'store'])->name('information-sources.store');
        Route::put('information-sources/{informationSource}/restore', [InformationSourceController::class, 'restore'])->name('information-sources.restore');
        Route::delete('information-sources/{informationSource}', [InformationSourceController::class, 'destroy'])->name('information-sources.destroy');
    });
});
