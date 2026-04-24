<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateRankingController;
use App\Http\Controllers\Api\JobDescriptionController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\AiInsightController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CandidateMailController;
use App\Http\Controllers\Api\DashboardController;

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
        Route::patch('/users/{user}/role',    [UserManagementController::class, 'assignRole']);
        Route::delete('/users/{user}',        [UserManagementController::class, 'destroy']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });

    // Dashboard stats
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Job Descriptions — full CRUD
    Route::get('/jobs',          [JobDescriptionController::class, 'index']);
    Route::post('/jobs',         [JobDescriptionController::class, 'store']);
    Route::get('/jobs/{job}',    [JobDescriptionController::class, 'show']);
    Route::put('/jobs/{job}',    [JobDescriptionController::class, 'update']);
    Route::delete('/jobs/{job}', [JobDescriptionController::class, 'destroy']);

    // Resume Controller
    Route::post('/resumes', [ResumeController::class, 'store']);
    Route::get('/resumes',  [ResumeController::class, 'index']);
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy']);

    // US-014 + US-015 + US-018: ranked list with filters and export csv file
    /// export must be before the index to avoid Laravel matching "export" as an {id} parameter
    Route::get('/candidate-rankings/export', [CandidateRankingController::class, 'export']);
    Route::get('/candidate-rankings', [CandidateRankingController::class, 'index']);
    // update candidate status from ranking page
    Route::patch('/candidate-rankings/{resumeId}/status', [CandidateRankingController::class, 'updateStatus']);

    // US-016 + US-017: AI insights for a resume
    Route::post('/resumes/{resumeId}/ai-insights', [AiInsightController::class, 'generate']);
    Route::get('/resumes/{resumeId}/ai-insights',  [AiInsightController::class, 'show']);

    // Email templates and sending
    Route::get('/candidates/mail-template', [CandidateMailController::class, 'template']);
    Route::post('/candidates/send-mail',    [CandidateMailController::class, 'send']);
    Route::post('/candidates/mail/send-bulk', [CandidateMailController::class, 'sendBulk']);
    Route::get('/candidates/mail/bulk-preview', [CandidateMailController::class, 'bulkPreview']);
});
