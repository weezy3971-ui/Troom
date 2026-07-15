<?php

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
