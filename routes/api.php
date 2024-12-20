<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ClassGroupController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FeePlanPeriodController;
use App\Http\Controllers\FeePlanController;
use App\Http\Controllers\Auth\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/logout', [AuthController::class, 'logout']);

    // Student Routes
    Route::prefix('student')->group(function () {
        Route::get('/view', [StudentController::class, 'index']);          // List all students
        Route::get('/view/{id}', [StudentController::class, 'index']);     // Get details of a single student
        Route::post('/', [StudentController::class, 'register']);         // Add a new student (Admin only)
        Route::post('/{id}', [StudentController::class, 'update']);     // Update a student (Admin only)
        Route::delete('/{id}', [StudentController::class, 'destroy']); // Delete a student (Admin only)

        Route::get('/import_basic', [StudentController::class, 'importStudentCsv']); 
        Route::get('/import_details', [StudentController::class, 'importDetailsCsv']); 
    });

    Route::prefix('teacher')->group(function () {
        Route::post('/', [TeacherController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [TeacherController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/{id?}', [TeacherController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [TeacherController::class, 'destroy']); // Delete a teacher
    });

    Route::prefix('academic_year')->group(function () {
        Route::post('/', [AcademicYearController::class, 'create']);         // Add a new teacher (Admin only)
        Route::get('/view/{id}', [AcademicYearController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/view/{id?}', [AcademicYearController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [AcademicYearController::class, 'destroy']); // Delete a teacher

        Route::get('/import', [AcademicYearController::class, 'importCsv']); 
    });

    Route::prefix('class_group')->group(function () {
        Route::post('/', [ClassGroupController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [ClassGroupController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/view/{id?}', [ClassGroupController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [ClassGroupController::class, 'destroy']); // Delete a teacher

        Route::get('/import', [ClassGroupController::class, 'importCsv']); 
    });

    
    Route::prefix('student_class')->group(function () {
        Route::post('/', [StudentClassController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [StudentClassController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/{id?}', [StudentClassController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [StudentClassController::class, 'destroy']); // Delete a teacher
    });

    Route::prefix('fee')->group(function () {
        Route::get('/{id?}', [FeeController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeeController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeeController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeeController::class, 'destroy']); // Delete a record
    });

    Route::prefix('fee_plan_period')->group(function () {
        Route::get('/{id?}', [FeePlanPeriodController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeePlanPeriodController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeePlanPeriodController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanPeriodController::class, 'destroy']); // Delete a record
    });

    Route::prefix('fee_plan')->group(function () {
        Route::get('/{id?}', [FeePlanController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeePlanController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeePlanController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanController::class, 'destroy']); // Delete a record
    });

});