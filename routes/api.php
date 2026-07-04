<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show']);
Route::get('health/readiness', [HealthController::class, 'readiness']);
