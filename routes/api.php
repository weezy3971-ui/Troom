<?php

use App\Http\Controllers\Api\MpesaWebhookController;
use App\Http\Controllers\WeighScaleReadingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (stateless — no session / CSRF)
|--------------------------------------------------------------------------
*/

// Digital weighing scale pushes readings here with a shared bearer token
// (config: services.weigh_scale.token). Token verified inside the controller.
Route::post('weigh-scale/readings', [WeighScaleReadingController::class, 'ingest'])
    ->name('api.weigh-scale.ingest');

// Safaricom Daraja callbacks — register these four URLs on go-live. Unused
// while MPESA_DRIVER=fake (the default); see MpesaWebhookController.
Route::post('mpesa/c2b/validation', [MpesaWebhookController::class, 'c2bValidation'])->name('api.mpesa.c2b.validation');
Route::post('mpesa/c2b/confirmation', [MpesaWebhookController::class, 'c2bConfirmation'])->name('api.mpesa.c2b.confirmation');
Route::post('mpesa/b2c/result', [MpesaWebhookController::class, 'b2cResult'])->name('api.mpesa.b2c.result');
Route::post('mpesa/b2c/timeout', [MpesaWebhookController::class, 'b2cTimeout'])->name('api.mpesa.b2c.timeout');
