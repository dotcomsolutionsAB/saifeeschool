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
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AttendanceController;
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

    // Teacher Routes
    Route::prefix('teacher')->group(function () {
        Route::post('/', [TeacherController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [TeacherController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/{id?}', [TeacherController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [TeacherController::class, 'destroy']); // Delete a teacher
    });

    // Academic Year Routes
    Route::prefix('academic_year')->group(function () {
        Route::post('/', [AcademicYearController::class, 'create']);         // Add a new teacher (Admin only)
        Route::get('/view/{id}', [AcademicYearController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/view/{id?}', [AcademicYearController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [AcademicYearController::class, 'destroy']); // Delete a teacher

        Route::get('/import', [AcademicYearController::class, 'importCsv']); 
    });

    // Class-group Routes
    Route::prefix('class_group')->group(function () {
        Route::post('/', [ClassGroupController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [ClassGroupController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/view/{id?}', [ClassGroupController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [ClassGroupController::class, 'destroy']); // Delete a teacher

        Route::get('/import', [ClassGroupController::class, 'importCsv']); 
    });

    // Student-class Routes
    Route::prefix('student_class')->group(function () {
        Route::post('/', [StudentClassController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [StudentClassController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/{id?}', [StudentClassController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [StudentClassController::class, 'destroy']); // Delete a teacher
    });

    // Fee Routes
    Route::prefix('fee')->group(function () {
        Route::get('/view/{id?}', [FeeController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeeController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeeController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeeController::class, 'destroy']); // Delete a record

        Route::get('/import', [FeeController::class, 'importCsv']); 
    });

    // Fee-plan-period Routes
    Route::prefix('fee_plan_period')->group(function () {
        Route::get('/view/{id?}', [FeePlanPeriodController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeePlanPeriodController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeePlanPeriodController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanPeriodController::class, 'destroy']); // Delete a record

        Route::get('/import', [FeePlanPeriodController::class, 'importCsv']); 
    });

    // Fee-plan Routes
    Route::prefix('fee_plan')->group(function () {
        Route::get('/view/{id?}', [FeePlanController::class, 'index']); // Fetch all or one record
        Route::post('/', [FeePlanController::class, 'register']); // Create a new record
        Route::post('/{id}', [FeePlanController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanController::class, 'destroy']); // Delete a record

        Route::get('/import', [FeePlanController::class, 'importCsv']); 
    });

    // Suppliers Routes
    Route::prefix('supplier')->group(function () {
        Route::get('/view', [SupplierController::class, 'index']);          // List all Suppliers
        Route::get('/view/{id}', [SupplierController::class, 'index']);     // Get details of a single Suppliers
        Route::post('/', [SupplierController::class, 'register']);         // Add a new Suppliers (Admin only)
        Route::post('/{id}', [SupplierController::class, 'update']);     // Update a Suppliers (Admin only)
        Route::delete('/{id}', [SupplierController::class, 'destroy']); // Delete a Suppliers (Admin only)

        Route::get('/import', [SupplierController::class, 'importCsv']); 
    });

    // Items Routes
    Route::prefix('item')->group(function () {
        Route::get('/view', [ItemController::class, 'index']);          // List all Items
        Route::get('/view/{id}', [ItemController::class, 'index']);     // Get details of a single Items
        Route::post('/', [ItemController::class, 'register']);         // Add a new Items (Admin only)
        Route::post('/{id}', [ItemController::class, 'update']);     // Update a Items (Admin only)
        Route::delete('/{id}', [ItemController::class, 'destroy']); // Delete a Items (Admin only)

        Route::get('/import', [ItemController::class, 'importCsv']); 
    });

     // Purchase Routes
     Route::prefix('attendance')->group(function () {
        Route::get('/view', [PurchaseController::class, 'index']);          // List all Purchase
        Route::get('/view/{id}', [PurchaseController::class, 'index']);     // Get details of a single Purchase
        Route::post('/', [AttendanceController::class, 'registerandUpdate']);         // Add a new Purchase (Admin only)
        // Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Purchase (Admin only)
        // Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [AttendanceController::class, 'importCsv']); 
    });
    // Purchase Routes
    Route::prefix('purchase')->group(function () {
        Route::get('/view', [PurchaseController::class, 'index']);          // List all Purchase
        Route::get('/view/{id}', [PurchaseController::class, 'index']);     // Get details of a single Purchase
        Route::post('/', [PurchaseController::class, 'register']);         // Add a new Purchase (Admin only)
        Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Purchase (Admin only)
        Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [PurchaseController::class, 'importCsv']); 
    });
});
