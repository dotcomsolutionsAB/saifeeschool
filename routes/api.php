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
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\MarksController;
use App\Http\Controllers\CharacterCertificateController;
use App\Http\Controllers\TransferCertificateController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\NewAdmissionController;
use App\Http\Controllers\TeacherApplicationController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\PGResponseController;
use App\Http\Controllers\DailyTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\PermissionRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/logout', [AuthController::class, 'logout']);

    Route::post('/password', [UserController::class, 'change_password']);          // Update password

    Route::post('/new_password', [UserController::class, 'updatePassword']);  // New password

    // Student Routes
    Route::prefix('student')->group(function () {
        Route::post('/view', [StudentController::class, 'index']);          // List all students
        // Route::post('/view/{id}', [StudentController::class, 'index']);     // Get details of a single student
        Route::post('/register', [StudentController::class, 'register']);         // Add a new student (Admin only)
        Route::post('/update/{id}', [StudentController::class, 'update']);     // Update a student (Admin only)
        Route::delete('/{id}', [StudentController::class, 'destroy']); // Delete a student (Admin only)

        Route::post('/duplicate', [StudentController::class, 'fetch_duplicate']); // Get duplicate student roll

        Route::post('/export', [StudentController::class, 'export']); // Get export student roll

        Route::get('/import_basic', [StudentController::class, 'importStudentCsv']); 
        Route::get('/import_details', [StudentController::class, 'importDetailsCsv']); 

        Route::post('/make_payment', [StudentController::class, 'initiatePayment']);

        Route::get('/fetch_fees', [StudentController::class, 'fetchStudentFees']);

        Route::get('/fetch_photos', [StudentController::class, 'migrateUploadsFromCsv']);

        Route::post('/upgrade', [StudentController::class, 'upgrade_student']);

        Route::post('/apply_fee', [StudentController::class, 'apply_fee_plan']);
    });

    // Route::post('/duplicate', [StudentController::class, 'fetch_duplicate']);

    // Teacher Routes
    Route::prefix('teacher')->group(function () {
        Route::post('/', [TeacherController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/{id}', [TeacherController::class, 'update']);     // Update a teacher (Admin only)
        Route::get('/view/{id?}', [TeacherController::class, 'index']);  // Fetch all or specific teacher
        Route::delete('/{id}', [TeacherController::class, 'destroy']); // Delete a teacher

        Route::get('/import', [TeacherController::class, 'importCsv']); 

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
        Route::post('/register', [ClassGroupController::class, 'create']);         // Add a new teacher (Admin only)
        Route::post('/update/{id}', [ClassGroupController::class, 'update']);     // Update a teacher (Admin only)
        Route::post('/view/{id?}', [ClassGroupController::class, 'index']);  // Fetch all or specific teacher
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
        Route::post('/view/{id?}', [FeeController::class, 'index']); // Fetch all or one record
        Route::post('/register', [FeeController::class, 'register']); // Create a new record
        Route::post('/update/{id}', [FeeController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeeController::class, 'destroy']); // Delete a record

        Route::post('/generate_pending_fees_pdf', [FeeController::class, 'generatePendingFeesPDF']);

        Route::get('/import', [FeeController::class, 'importCsv']); 
    });

    // Fee-plan-period Routes
    Route::prefix('fee_plan_period')->group(function () {
        Route::post('/view/{id?}', [FeePlanPeriodController::class, 'index']); // Fetch all or one record
        Route::post('/register', [FeePlanPeriodController::class, 'register']); // Create a new record
        Route::post('/update/{id}', [FeePlanPeriodController::class, 'update']); // Update a record
        Route::delete('/{id}', [FeePlanPeriodController::class, 'destroy']); // Delete a record

        Route::get('/import', [FeePlanPeriodController::class, 'importCsv']); 
    });

    // Fee-plan Routes
    Route::prefix('fee_plan')->group(function () {
        Route::post('/view/{id?}', [FeePlanController::class, 'index']); // Fetch all or one record
        Route::post('/register', [FeePlanController::class, 'register']); // Create a new record
        Route::post('/update/{id}', [FeePlanController::class, 'update']); // Update a record
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
        Route::get('/view', [AttendanceController::class, 'index']);          // List all Purchase
        Route::get('/view/{id}', [AttendanceController::class, 'index']);     // Get details of a single Purchase
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

     // Purchase Routes
     Route::prefix('subject')->group(function () {
        // Route::get('/view', [MarksController::class, 'index']);          // List all Purchase
        // Route::get('/view/{id}', [MarksController::class, 'index']);     // Get details of a single Purchase
        // Route::post('/', [MarksController::class, 'registerandUpdate']);         // Add a new Purchase (Admin only)
        // Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Purchase (Admin only)
        // Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [SubjectController::class, 'importCsv']); 

        Route::get('/view/{id?}', [SubjectController::class, 'index']); 
    });

    // Purchase Routes
    Route::prefix('marks')->group(function () {
        Route::get('/view', [MarksController::class, 'index']);          // List all Purchase
        Route::get('/view/{id}', [MarksController::class, 'index']);     // Get details of a single Purchase
        Route::post('/', [MarksController::class, 'registerandUpdate']);         // Add a new Purchase (Admin only)
        // Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Purchase (Admin only)
        // Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [MarksController::class, 'importCsv']); 
    });

    Route::prefix('counter')->group(function () {
        Route::get('/view', [CounterController::class, 'index']); // List all counters
        Route::post('/', [CounterController::class, 'store']); // Create a new counter
        Route::get('/view/{id?}', [CounterController::class, 'index']); // Get a specific counter
        Route::post('/update/{id}', [CounterController::class, 'update']); // Update a specific counter
        Route::delete('/{id}', [CounterController::class, 'destroy']); // Delete a specific counter
        Route::post('/increment', [CounterController::class, 'increment']); // Increment a counter
    });

    // Character Certificate Routes
    Route::prefix('character_certificate')->group(function () {
        Route::get('/view', [CharacterCertificateController::class, 'index']);          // List all Purchase
        Route::post('/register/{id?}', [CharacterCertificateController::class, 'storeOrUpdate']);         // Add a new Purchase (Admin only)
        Route::delete('/{id}', [CharacterCertificateController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [CharacterCertificateController::class, 'importCsv']); 

        Route::post('/bulk', [CharacterCertificateController::class, 'bulkStore']); 
    });

    // Character Certificate Routes
    Route::prefix('transfer_certificate')->group(function () {
        Route::get('/view', [TransferCertificateController::class, 'index']);          // List all Purchase
        Route::post('/{id?}', [TransferCertificateController::class, 'storeOrUpdate']);         // Add a new Purchase (Admin only)
        Route::delete('/{id}', [TransferCertificateController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [TransferCertificateController::class, 'importCsv']); 
    });

    // New Admission Routes
    Route::prefix('new_admission')->group(function () {
        Route::get('/view', [NewAdmissionController::class, 'view']);          // List all Purchase
        Route::post('/register', [NewAdmissionController::class, 'register']); // Register a specific child
        Route::post('/update/{id}', [NewAdmissionController::class, 'update']); // Update a specific child
        Route::delete('/{id}', [NewAdmissionController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [NewAdmissionController::class, 'importCsv']); 
    });

    // Teacher Application Routes
    Route::prefix('teacher_application')->group(function () {
        Route::get('/view', [TeacherApplicationController::class, 'view']);          // List all Purchase
        Route::post('/register', [TeacherApplicationController::class, 'register']); // Register a specific child
        Route::post('/update/{id}', [TeacherApplicationController::class, 'update']); // Update a specific child
        Route::delete('/{id}', [TeacherApplicationController::class, 'destroy']); // Delete a Purchase (Admin only)
    
    });
    Route::get('/import_pg_response', [PGResponseController::class, 'importCsv']);

    Route::prefix('transactions')->group(function () {

        Route::get('/records', [DailyTransactionController::class, 'index']);

        Route::get('/export', [DailyTransactionController::class, 'exportToExcel']);

    });

    Route::get('/import_txn', [TransactionController::class, 'importCsv']);

    Route::get('/import_txn_type', [TransactionTypeController::class, 'importCsv']);

    Route::get('/fetch_txns', [TransactionTypeController::class, 'fetchTransactions']);

    Route::post('/create-order', [RazorpayController::class, 'createOrder']);
    Route::get('/payment-status/{paymentId}', [RazorpayController::class, 'fetchPaymentStatus']);
    Route::get('/order-status/{orderId}', [RazorpayController::class, 'fetchOrderStatus']);


    Route::prefix('users')->group(function () {
    
        Route::post('/assign_permissions', [PermissionRoleController::class, 'assignPermissionsToUser']);
        Route::get('/{userId}/permissions', [PermissionRoleController::class, 'getUserPermissions']);
        Route::get('/with_permissions', [PermissionRoleController::class, 'getUsersWithPermissions']);
    });

    Route::post('/users/remove-permissions', [PermissionRoleController::class, 'removePermissionsFromUser']);

    // Permissions and Roles
    Route::prefix('permissions')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createPermission']);
        Route::post('/create-bulk', [PermissionRoleController::class, 'createBulkPermissions']);
        Route::get('/all', [PermissionRoleController::class, 'getAllPermissions']);
        // Route::delete('/delete', [PermissionRoleController::class, 'deletePermission']);
    });

    Route::prefix('roles')->group(function () {
        Route::post('/create', [PermissionRoleController::class, 'createRole']);
        Route::post('/add-permissions', [PermissionRoleController::class, 'addPermissionsToRole']);
        Route::get('/all', [PermissionRoleController::class, 'getAllRoles']);
        Route::get('/{roleName}/permissions', [PermissionRoleController::class, 'getRolePermissions']);
        Route::post('/create-with-permissions', [PermissionRoleController::class, 'createRoleWithPermissions']);
    });

    // Route::middleware(['auth:sanctum', 'check-api-permission:manage-users'])->group(function () {
    //     // Route::get('/secure_dashboard', [DashboardController::class, 'index']);
    //     Route::prefix('student')->group(function () {
    //         Route::post('/view', [StudentController::class, 'index']); 
    //     });
    // });

    // Route::middleware(['auth:sanctum',  \App\Http\Middleware\CheckApiPermission::class . ':manage-users'
    // ])->group(function () {
    //         // Route::get('/secure_dashboard', [DashboardController::class, 'index']);
    //         Route::prefix('student')->group(function () {
    //             Route::post('/view', [StudentController::class, 'index']); 
    //         });
    // });

    Route::get('/dashboard', [DashboardController::class, 'dashboard']);

    });
