<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobDescriptionController;
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

    // Job Descriptions — full CRUD
    Route::get('/jobs',          [JobDescriptionController::class, 'index']);
    Route::post('/jobs',         [JobDescriptionController::class, 'store']);
    Route::get('/jobs/{job}',    [JobDescriptionController::class, 'show']);
    Route::put('/jobs/{job}',    [JobDescriptionController::class, 'update']);
    Route::delete('/jobs/{job}', [JobDescriptionController::class, 'destroy']);

    // Admin-only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users',                  [UserManagementController::class, 'index']);
        Route::patch('/users/{user}/role',    [UserManagementController::class, 'assignRole']);
        Route::delete('/users/{user}',        [UserManagementController::class, 'destroy']);
    });
});
