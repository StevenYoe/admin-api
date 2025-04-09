<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\DivisionController;
use App\Http\Controllers\API\PositionController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health check endpoint - public
Route::get('/health', [HealthController::class, 'check']);

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Dashboard
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    
    // User management
    Route::apiResource('users', UserController::class);
    
    // Role management
    Route::get('/roles/all', [RoleController::class, 'all']);
    Route::apiResource('roles', RoleController::class);
    
    // Division management
    Route::get('/divisions/all', [DivisionController::class, 'all']);
    Route::apiResource('divisions', DivisionController::class);
    
    // Position management
    Route::get('/positions/all', [PositionController::class, 'all']);
    Route::apiResource('positions', PositionController::class);
});
