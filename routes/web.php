<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

// Health check web interface
Route::middleware('auth')->get('/health', HealthCheckResultsController::class)->name('health.dashboard');
