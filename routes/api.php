<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;

/*
|--------------------------------------------------------------------------
| Public Routes — No token needed
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes — Must send Bearer token in header
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Admin-only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users',                  [UserManagementController::class, 'index']);
        Route::patch('/users/{userId}/role',    [UserManagementController::class, 'assignRole']);
        Route::delete('/users/{userId}',        [UserManagementController::class, 'destroy']);
    });
});
