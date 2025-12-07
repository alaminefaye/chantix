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
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class)->names([
        'index' => 'api.projects.index',
        'store' => 'api.projects.store',
        'show' => 'api.projects.show',
        'update' => 'api.projects.update',
        'destroy' => 'api.projects.destroy',
    ]);
    
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
        // Route spécifique pour l'audio doit être AVANT les routes génériques avec {progress}
        Route::get('/progress/{progress}/audio', [\App\Http\Controllers\Api\ProgressController::class, 'downloadAudio']);
        Route::match(['put', 'post'], '/progress/{progress}', [\App\Http\Controllers\Api\ProgressController::class, 'update']);
        Route::delete('/progress/{progress}', [\App\Http\Controllers\Api\ProgressController::class, 'destroy']);
        
        // Matériaux du projet
        Route::get('/materials', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'index']);
        Route::post('/materials', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'store']);
        Route::put('/materials/{material}', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'update']);
        Route::delete('/materials/{material}', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'destroy']);
        
        // Employés du projet
        Route::get('/employees', [\App\Http\Controllers\Api\ProjectEmployeeController::class, 'index']);
        Route::post('/employees', [\App\Http\Controllers\Api\ProjectEmployeeController::class, 'store']);
        Route::delete('/employees/{employee}', [\App\Http\Controllers\Api\ProjectEmployeeController::class, 'destroy']);
    });
    
    // Matériaux
    Route::apiResource('materials', \App\Http\Controllers\Api\MaterialController::class)->names([
        'index' => 'api.materials.index',
        'store' => 'api.materials.store',
        'show' => 'api.materials.show',
        'update' => 'api.materials.update',
        'destroy' => 'api.materials.destroy',
    ]);
    
    // Employés
    Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class)->names([
        'index' => 'api.employees.index',
        'store' => 'api.employees.store',
        'show' => 'api.employees.show',
        'update' => 'api.employees.update',
        'destroy' => 'api.employees.destroy',
    ]);
    
    // Dépenses
    Route::get('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'index']);
    Route::get('/expenses/{id}', [\App\Http\Controllers\Api\ExpenseController::class, 'show']);
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'index']);
        Route::post('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'store']);
        Route::get('/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'show']);
        Route::put('/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'destroy']);
    });
    
    // Tâches
    Route::get('/tasks', [\App\Http\Controllers\Api\TaskController::class, 'index']);
    Route::get('/tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'show']);
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/tasks', [\App\Http\Controllers\Api\TaskController::class, 'index']);
        Route::post('/tasks', [\App\Http\Controllers\Api\TaskController::class, 'store']);
        Route::get('/tasks/{task}', [\App\Http\Controllers\Api\TaskController::class, 'show']);
        Route::put('/tasks/{task}', [\App\Http\Controllers\Api\TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [\App\Http\Controllers\Api\TaskController::class, 'destroy']);
    });
    
    // Commentaires
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/comments', [\App\Http\Controllers\Api\CommentController::class, 'index']);
        Route::post('/comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);
        Route::get('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'show']);
        Route::delete('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);
    });
    
    // Rapports
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
        Route::post('/reports/generate', [\App\Http\Controllers\Api\ReportController::class, 'generate']);
        Route::get('/reports/{report}', [\App\Http\Controllers\Api\ReportController::class, 'show']);
        Route::delete('/reports/{report}', [\App\Http\Controllers\Api\ReportController::class, 'destroy']);
    });
    
    // Tokens FCM pour les notifications push
    Route::prefix('fcm-tokens')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\FcmTokenController::class, 'store']);
        Route::get('/', [\App\Http\Controllers\Api\FcmTokenController::class, 'index']);
        Route::delete('/', [\App\Http\Controllers\Api\FcmTokenController::class, 'destroy']);
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::get('/latest', [\App\Http\Controllers\Api\NotificationController::class, 'latest']);
        Route::post('/{id}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    });
});

