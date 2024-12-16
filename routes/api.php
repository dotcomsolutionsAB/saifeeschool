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
    Route::get('/register', [StudentController::class, 'index']);          // List all products
    Route::get('/{id}', [StudentController::class, 'index']);     // Get details of a single product
    Route::post('/', [StudentController::class, 'register']);         // Add a new product (Admin only)
    Route::post('/{id}', [StudentController::class, 'update']);     // Update a product (Admin only)
    Route::delete('/{id}', [StudentController::class, 'destroy']); // Delete a product (Admin only)

    Route::post('/import', [ProductController::class, 'importProductsFromCsv']);
});

});