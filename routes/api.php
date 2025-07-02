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

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Distance routes
    Route::prefix('distance')->group(function () {
        Route::get('/', [DistanceController::class, 'index']);
        Route::get('/chart-data', [DistanceController::class, 'chartData']);
    });
    
    // Engine Hours routes
    Route::prefix('engine-hours')->group(function () {
        Route::get('/', [EngineHoursController::class, 'index']);
        Route::get('/chart-data', [EngineHoursController::class, 'chartData']);
    });
    
    // Activity Breakdown routes
    Route::prefix('activity-breakdown')->group(function () {
        Route::get('/', [ActivityBreakdownController::class, 'index']);
        Route::get('/chart-data', [ActivityBreakdownController::class, 'chartData']);
        Route::get('/by-date/{date}', [ActivityBreakdownController::class, 'byDate']);
    });
    
    // Messages Received routes
    Route::prefix('messages-received')->group(function () {
        Route::get('/', [MessagesReceivedController::class, 'index']);
        Route::get('/chart-data', [MessagesReceivedController::class, 'chartData']);
        Route::get('/statistics', [MessagesReceivedController::class, 'statistics']);
    });
});

// Public routes (if any) can be added here
// Example: Route::get('/public-endpoint', [Controller::class, 'method']);
