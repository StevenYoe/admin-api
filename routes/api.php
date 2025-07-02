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

// API route definitions for the application.
// This file defines all endpoints for authentication, health check, and resource management.

// Health check endpoint - public
// Returns the health status of the API and its services.
Route::get('/health', [HealthController::class, 'check']);

// Authentication routes
// Login endpoint for user authentication.
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // User authentication endpoints
    // Get current authenticated user
    Route::get('/me', [AuthController::class, 'me']);
    // Logout and revoke token
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Dashboard statistics endpoint
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    
    // User management endpoints (CRUD)
    Route::apiResource('users', UserController::class);
    
    // Role management endpoints (CRUD and get all active roles)
    Route::get('/roles/all', [RoleController::class, 'all']);
    Route::apiResource('roles', RoleController::class);
    
    // Division management endpoints (CRUD and get all active divisions)
    Route::get('/divisions/all', [DivisionController::class, 'all']);
    Route::apiResource('divisions', DivisionController::class);
    
    // Position management endpoints (CRUD and get all active positions)
    Route::get('/positions/all', [PositionController::class, 'all']);
    Route::apiResource('positions', PositionController::class);
});
