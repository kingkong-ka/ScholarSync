<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EducatorController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\StudentManualController;
use App\Http\Controllers\ManualController;

Route::get('/', function () {
    return redirect('/login');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/student/logout', [AuthController::class, 'logout'])->name('student.logout');

// Admin Routes
Route::prefix('admin')->middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

// Educator Routes
Route::prefix('educator')->middleware(['auth', \App\Http\Middleware\EducatorMiddleware::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [EducatorController::class, 'dashboard'])->name('educator.dashboard');
    
    // Violations Listing
    Route::get('/violation', [ViolationController::class, 'index'])->name('educator.violation');
    
    // Add Violator Form and Submission
    Route::get('/add-violator', [ViolationController::class, 'addViolatorForm'])->name('educator.add-violator-form');
    Route::post('/add-violator', [ViolationController::class, 'addViolatorSubmit'])->name('educator.add-violator');
    
    // Edit and Update Violation
    Route::get('/edit-violation/{id}', [ViolationController::class, 'editViolation'])->name('educator.edit-violation');
    Route::put('/update-violation/{id}', [ViolationController::class, 'updateViolation'])->name('educator_update_violation');
    
    // View Violation
    Route::get('/view-violation/{id}', [EducatorController::class, 'viewViolation'])->name('educator.view-violation');
    
    // New Violation Type Form and Submission
    Route::get('/new-violation', [EducatorController::class, 'createViolationType'])->name('educator.new-violation');
    Route::get('/add-violation', [EducatorController::class, 'createViolationType'])->name('educator.add-violation');
    Route::post('/add-violation-type', [ViolationController::class, 'storeViolationType'])->name('educator.add-violation-type');
    
    // API Routes for Form Data
    Route::get('/violation-form-data', [ViolationController::class, 'getFormData'])->name('educator.violation-form-data');
    Route::get('/violation-types/{categoryId}', [ViolationController::class, 'getViolationTypesByCategory']);
    
    // Additional routes for the student dashboard
    Route::get('/student-violations', [ViolationController::class, 'studentViolations'])->name('educator.student-violations');
    
    // Route for filtering students by penalty
    Route::get('/students-by-penalty/{penalty}', [EducatorController::class, 'studentsByPenalty'])->name('educator.students-by-penalty');
    
    // Behavior route
    Route::get('/behavior', [EducatorController::class, 'behavior'])->name('educator.behavior');
    
    // Rewards routes
    Route::get('/rewards', [RewardController::class, 'index'])->name('educator.rewards');
    Route::get('/rewards/add', [RewardController::class, 'create'])->name('rewards.add');
    Route::post('/rewards/store', [RewardController::class, 'store'])->name('rewards.store');
    Route::get('/rewards/edit/{id}', [RewardController::class, 'edit'])->name('rewards.edit');
    Route::put('/rewards/update/{id}', [RewardController::class, 'update'])->name('rewards.update');
    Route::delete('/rewards/destroy/{id}', [RewardController::class, 'destroy'])->name('rewards.destroy');
    Route::post('/rewards/generate-monthly-points', [RewardController::class, 'generateMonthlyPoints'])->name('rewards.generate-monthly-points');
});

// Student routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
    
    // Violation, behavior, reward routes
    Route::get('/violation', [StudentController::class, 'violation'])->name('student.violation');
    Route::get('/behavior', [StudentController::class, 'behavior'])->name('student.behavior');
    Route::get('/reward', [StudentController::class, 'reward'])->name('student.reward');
    
    // Manual routes
    // Route::get('/student-manual', function() {
    //     return view('student-manual');
    // })->name('student-manual');
    // Route::get('/student/manual', function() {
    //     return view('student.manual');
    // })->name('student.manual')
    
    Route::get('/student-manual', [StudentManualController::class, 'index'])->name('student-manual');
    Route::get('/manual', [ManualController::class, 'index'])->name('manual');

    


    // Points and redemption routes
    Route::get('/earn-points', [StudentController::class, 'earnPoints'])->name('student.earn_points');
    Route::get('/redemption', [StudentController::class, 'redemption'])->name('student.redemption');
    
    // Notification route
    Route::get('/notifications', [AuthController::class, 'notifications'])->name('notification');
});

Route::fallback(function () {
    return redirect('/login');
});
