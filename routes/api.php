<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\UserController;

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

// Routes publiques (sans authentification)
Route::prefix('v1')->group(function () {
    // Authentification
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword']);
});

// Routes protégées (nécessitent une authentification)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Utilisateur
    Route::get('/user', [UserController::class, 'getCurrentUser']);
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'apiIndex']);
    
    // Projets
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class);
    
    // Pointage
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/attendances', [\App\Http\Controllers\Api\AttendanceController::class, 'index']);
        Route::post('/attendances/check-in', [\App\Http\Controllers\Api\AttendanceController::class, 'checkIn']);
        Route::post('/attendances/{attendance}/check-out', [\App\Http\Controllers\Api\AttendanceController::class, 'checkOut']);
        Route::post('/attendances/absence', [\App\Http\Controllers\Api\AttendanceController::class, 'absence']);
    });
    
    // Avancement
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/progress', [\App\Http\Controllers\Api\ProgressController::class, 'index']);
        Route::post('/progress', [\App\Http\Controllers\Api\ProgressController::class, 'store']);
        Route::delete('/progress/{progress}', [\App\Http\Controllers\Api\ProgressController::class, 'destroy']);
    });
});

