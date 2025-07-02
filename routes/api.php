<?php

use App\Http\Controllers\API\DistanceController;
use App\Http\Controllers\API\EngineHoursController;
use App\Http\Controllers\API\ActivityBreakdownController;
use App\Http\Controllers\API\MessagesReceivedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes for development
Route::prefix('distance')->group(function () {
    Route::get('/', [DistanceController::class, 'index']);
    Route::get('/chart-data', [DistanceController::class, 'chartData']);
});

Route::prefix('engine-hours')->group(function () {
    Route::get('/', [EngineHoursController::class, 'index']);
    Route::get('/chart-data', [EngineHoursController::class, 'chartData']);
});

Route::prefix('activity-breakdown')->group(function () {
    Route::get('/', [ActivityBreakdownController::class, 'index']);
    Route::get('/chart-data', [ActivityBreakdownController::class, 'chartData']);
    Route::get('/by-date/{date}', [ActivityBreakdownController::class, 'byDate']);
});

Route::prefix('messages-received')->group(function () {
    Route::get('/', [MessagesReceivedController::class, 'index']);
    Route::get('/chart-data', [MessagesReceivedController::class, 'chartData']);
    Route::get('/statistics', [MessagesReceivedController::class, 'statistics']);
});
