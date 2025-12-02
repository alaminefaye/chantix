<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProjectController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
    // Password Reset
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
    
    // Email Verification
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->middleware('auth')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\AuthController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\AuthController::class, 'resendVerification'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');
});

Route::middleware(['auth', 'verified', 'company'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    
    // Companies
    Route::resource('companies', CompanyController::class)->except(['destroy']);
    Route::post('/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    
    // Invitations
    Route::prefix('companies/{company}')->name('invitations.')->group(function () {
        Route::get('/invitations', [\App\Http\Controllers\InvitationController::class, 'index'])->name('index');
        Route::get('/invitations/create', [\App\Http\Controllers\InvitationController::class, 'create'])->name('create');
        Route::post('/invitations', [\App\Http\Controllers\InvitationController::class, 'store'])->name('store');
        Route::post('/invitations/{invitation}/resend', [\App\Http\Controllers\InvitationController::class, 'resend'])->name('resend');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\InvitationController::class, 'destroy'])->name('destroy');
    });
    
    // Accepter une invitation (route publique)
    Route::get('/invitations/accept/{token}', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invitations.accept');
    
    // Projects
    Route::resource('projects', ProjectController::class);
    Route::get('/projects/{project}/timeline', [ProjectController::class, 'timeline'])->name('projects.timeline');
    Route::get('/projects/{project}/gallery', [ProjectController::class, 'gallery'])->name('projects.gallery');
    
    // Materials
    Route::resource('materials', \App\Http\Controllers\MaterialController::class);
    Route::get('/materials/import', [\App\Http\Controllers\MaterialController::class, 'showImport'])->name('materials.import');
    Route::post('/materials/import', [\App\Http\Controllers\MaterialController::class, 'import'])->name('materials.import.store');
    Route::get('/materials/template/download', [\App\Http\Controllers\MaterialController::class, 'downloadTemplate'])->name('materials.template.download');
    Route::post('/projects/{project}/materials/add', [\App\Http\Controllers\MaterialController::class, 'addToProject'])->name('projects.materials.add');
    Route::put('/projects/{project}/materials/{material}/update', [\App\Http\Controllers\MaterialController::class, 'updateProjectMaterial'])->name('projects.materials.update');
    Route::get('/projects/{project}/materials/{material}/transfer', [\App\Http\Controllers\MaterialController::class, 'showTransfer'])->name('projects.materials.transfer');
    Route::post('/projects/{project}/materials/{material}/transfer', [\App\Http\Controllers\MaterialController::class, 'transfer'])->name('projects.materials.transfer.store');
    
    // Employees
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::get('/employees/import', [\App\Http\Controllers\EmployeeController::class, 'showImport'])->name('employees.import');
    Route::post('/employees/import', [\App\Http\Controllers\EmployeeController::class, 'import'])->name('employees.import.store');
    Route::get('/employees/template/download', [\App\Http\Controllers\EmployeeController::class, 'downloadTemplate'])->name('employees.template.download');
    Route::post('/projects/{project}/employees/assign', [\App\Http\Controllers\EmployeeController::class, 'assignToProject'])->name('projects.employees.assign');
    Route::post('/projects/{project}/employees/{employee}/remove', [\App\Http\Controllers\EmployeeController::class, 'removeFromProject'])->name('projects.employees.remove');
    
    // Attendances (Pointage)
    Route::prefix('projects/{project}')->name('attendances.')->group(function () {
        Route::get('/attendances', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('index');
        Route::get('/attendances/create', [\App\Http\Controllers\AttendanceController::class, 'create'])->name('create');
        Route::post('/attendances/check-in', [\App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/attendances/{attendance}/check-out', [\App\Http\Controllers\AttendanceController::class, 'checkOut'])->name('check-out');
        Route::post('/attendances/absence', [\App\Http\Controllers\AttendanceController::class, 'markAbsence'])->name('absence');
        Route::put('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'update'])->name('update');
        Route::delete('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'destroy'])->name('destroy');
    });
    
    // Expenses (Dépenses)
    Route::prefix('projects/{project}')->name('expenses.')->group(function () {
        Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('index');
        Route::get('/expenses/create', [\App\Http\Controllers\ExpenseController::class, 'create'])->name('create');
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('store');
        Route::get('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'show'])->name('show');
        Route::get('/expenses/{expense}/edit', [\App\Http\Controllers\ExpenseController::class, 'edit'])->name('edit');
        Route::put('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('update');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
    });
    
    // Tasks (Tâches)
    Route::prefix('projects/{project}')->name('tasks.')->group(function () {
        Route::get('/tasks', [\App\Http\Controllers\TaskController::class, 'index'])->name('index');
        Route::get('/tasks/create', [\App\Http\Controllers\TaskController::class, 'create'])->name('create');
        Route::post('/tasks', [\App\Http\Controllers\TaskController::class, 'store'])->name('store');
        Route::get('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'show'])->name('show');
        Route::get('/tasks/{task}/edit', [\App\Http\Controllers\TaskController::class, 'edit'])->name('edit');
        Route::put('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'update'])->name('update');
        Route::delete('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'destroy'])->name('destroy');
    });
    
    // Reports (Rapports)
    Route::prefix('projects/{project}')->name('reports.')->group(function () {
        Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/reports/daily', [\App\Http\Controllers\ReportController::class, 'generateDaily'])->name('daily');
        Route::get('/reports/weekly', [\App\Http\Controllers\ReportController::class, 'generateWeekly'])->name('weekly');
        Route::get('/reports/daily/excel', [\App\Http\Controllers\ReportController::class, 'exportDailyExcel'])->name('daily.excel');
        Route::get('/reports/weekly/excel', [\App\Http\Controllers\ReportController::class, 'exportWeeklyExcel'])->name('weekly.excel');
    });
    
    // Comments (Commentaires/Chat)
    Route::prefix('projects/{project}')->name('comments.')->group(function () {
        Route::get('/comments', [\App\Http\Controllers\CommentController::class, 'index'])->name('index');
        Route::post('/comments', [\App\Http\Controllers\CommentController::class, 'store'])->name('store');
        Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])->name('destroy');
    });
    
    // Progress Updates
    Route::prefix('projects/{project}')->name('progress.')->group(function () {
        Route::get('/progress', [\App\Http\Controllers\ProgressUpdateController::class, 'index'])->name('index');
        Route::get('/progress/create', [\App\Http\Controllers\ProgressUpdateController::class, 'create'])->name('create');
        Route::post('/progress', [\App\Http\Controllers\ProgressUpdateController::class, 'store'])->name('store');
        Route::get('/progress/{progressUpdate}', [\App\Http\Controllers\ProgressUpdateController::class, 'show'])->name('show');
        Route::delete('/progress/{progressUpdate}', [\App\Http\Controllers\ProgressUpdateController::class, 'destroy'])->name('destroy');
    });
});

// UI Components (protégées par auth)
Route::middleware('auth')->group(function () {
    Route::prefix('ui')->name('ui.')->group(function () {
        Route::get('/buttons', function () {
            return view('ui.buttons');
        })->name('buttons');
        
        Route::get('/alerts', function () {
            return view('ui.alerts');
        })->name('alerts');
        
        Route::get('/card', function () {
            return view('ui.card');
        })->name('card');
        
        Route::get('/forms', function () {
            return view('ui.forms');
        })->name('forms');
        
        Route::get('/typography', function () {
            return view('ui.typography');
        })->name('typography');
        
        Route::get('/icons', function () {
            return view('ui.icons');
        })->name('icons');
    });

    // Sample Page
    Route::get('/sample-page', function () {
        return view('dashboard.sample-page');
    })->name('sample-page');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/api/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/api/notifications/latest', [\App\Http\Controllers\NotificationController::class, 'latest'])->name('notifications.latest');
});

// Routes Super Admin (accès sans vérification)
Route::middleware(['auth'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users-validation', [\App\Http\Controllers\Admin\UserValidationController::class, 'index'])->name('users-validation');
        Route::post('/users/{user}/verify', [\App\Http\Controllers\Admin\UserValidationController::class, 'verify'])->name('users.verify');
        Route::post('/users/{user}/reject', [\App\Http\Controllers\Admin\UserValidationController::class, 'reject'])->name('users.reject');
    });
});
