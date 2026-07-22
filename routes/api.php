<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Middleware\EnsureReadinessAccess;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show'])
    ->middleware('throttle:health');

Route::get('health/readiness', [HealthController::class, 'readiness'])
    ->middleware(['throttle:readiness', EnsureReadinessAccess::class]);
