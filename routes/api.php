<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum'])->group(function () {

    // Student Routes
    Route::prefix('student')->group(function () {
    Route::get('/', [StudentController::class, 'index']);          // List all students
    Route::get('/{id}', [StudentController::class, 'index']);     // Get details of a single student
    Route::post('/', [StudentController::class, 'register']);         // Add a new student (Admin only)
    Route::post('/{id}', [StudentController::class, 'update']);     // Update a student (Admin only)
    Route::delete('/{id}', [StudentController::class, 'destroy']); // Delete a student (Admin only)

    // Route::post('/import', [ProductController::class, 'importProductsFromCsv']);
});

    Route::prefix('teacher')->group(function () {
        Route::post('/', [TeacherController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [TeacherController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/{id?}', [TeacherController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [TeacherController::class, 'destroy']); // Delete a teacher
    });

    Route::prefix('fee')->group(function () {
        Route::get('/{id?}', [StudentFeeController::class, 'index']); // Fetch all or one record
        Route::post('/', [StudentFeeController::class, 'register']); // Create a new record
        Route::post('/{id}', [StudentFeeController::class, 'update']); // Update a record
        Route::delete('/{id}', [StudentFeeController::class, 'destroy']); // Delete a record
    });

    Route::prefix('fee-plan-particular')->group(function () {
        Route::get('/{id?}', [FeePlanParticularController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeePlanParticularController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeePlanParticularController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanParticularController::class, 'destroy']); // Delete a record
    });

});