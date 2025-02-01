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
        Route::post('/view', [StudentController::class, 'index'])
                ->middleware(['check-api-permission:students.full, students.view']);          // List all students

        Route::post('/view/{id}', [StudentController::class, 'getStudentDetails'])
                ->middleware(['check-api-permission:students.full, students.view']);     // Get details of a single student

        Route::post('/register', [StudentController::class, 'register'])
                ->middleware(['check-api-permission:students.full']);   

        Route::post('/upload', [StudentController::class, 'uploadFiles'])
                ->middleware(['check-api-permission:students.full']);   

        Route::post('/pending_fees', [StudentController::class, 'getUnpaidFees'])
                ->middleware(['check-api-permission:students.full']);   
                
         Route::post('/paid_fees', [StudentController::class, 'getPaidFees'])
                ->middleware(['check-api-permission:students.full']);   
                
                // Add a new student (Admin only)

        Route::post('/update/{id}', [StudentController::class, 'update'])
                ->middleware(['check-api-permission:students.full']);     // Update a student (Admin only)

        Route::delete('/{id}', [StudentController::class, 'destroy'])
                ->middleware(['check-api-permission:students.full']); // Delete a student (Admin only)

        Route::post('/duplicate', [StudentController::class, 'fetch_duplicate'])
                ->middleware(['check-api-permission:students.full']); // Get duplicate student roll

                

        Route::post('/export', [StudentController::class, 'export'])
                ->middleware(['check-api-permission:students.full, students.view']); // Get export student roll

        Route::get('/import_basic', [StudentController::class, 'importStudentCsv'])
                ->middleware(['check-api-permission:students.full']); 

        Route::get('/import_details', [StudentController::class, 'importDetailsCsv'])
                ->middleware(['check-api-permission:students.full']); 

        Route::post('/make_payment', [StudentController::class, 'initiatePayment'])
                ->middleware(['check-api-permission:students.full']);

        Route::get('/fetch_fees', [StudentController::class, 'fetchStudentFees'])
                ->middleware(['check-api-permission:students.full']);

        Route::get('/fetch_photos', [StudentController::class, 'migrateUploadsFromCsv'])
                ->middleware(['check-api-permission:students.full, students.view']);

        Route::post('/upgrade', [StudentController::class, 'upgrade_student'])
                ->middleware(['check-api-permission:students.full']);

        Route::post('/apply_fee', [StudentController::class, 'apply_fee_plan'])
                ->middleware(['check-api-permission:students.full']);
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
        Route::post('/view/{id?}', [FeeController::class, 'index'])
                ->middleware(['check-api-permission:fee.full, fee.view']); // Fetch all or one record

        Route::post('/register', [FeeController::class, 'register'])
                ->middleware(['check-api-permission:fee.full']); // Create a new record

        Route::post('/update/{id}', [FeeController::class, 'update'])
                ->middleware(['check-api-permission:fee.full']); // Update a record

        Route::delete('/{id}', [FeeController::class, 'destroy'])
                ->middleware(['check-api-permission:fee.full']); // Delete a record

        Route::post('/generate_pending_fees_pdf', [FeeController::class, 'generatePendingFeesPDF'])
                ->middleware(['check-api-permission:fee.full, fee.view']);

        Route::get('/import', [FeeController::class, 'importCsv'])
                ->middleware(['check-api-permission:fee.full']); 
    });

    // Fee-plan-period Routes
    Route::prefix('fee_plan_period')->group(function () {
        Route::post('/view/{id?}', [FeePlanPeriodController::class, 'index'])
                ->middleware(['check-api-permission:fee.full, fee.view']); // Fetch all or one record

        Route::post('/register', [FeePlanPeriodController::class, 'register'])
                ->middleware(['check-api-permission:fee.full']); // Create a new record

        Route::post('/update/{id}', [FeePlanPeriodController::class, 'update'])
                ->middleware(['check-api-permission:fee.full']); // Update a record

        Route::delete('/{id}', [FeePlanPeriodController::class, 'destroy'])
                ->middleware(['check-api-permission:fee.full']); // Delete a record

        Route::get('/import', [FeePlanPeriodController::class, 'importCsv'])
                ->middleware(['check-api-permission:fee.full']); 
    });

    // Fee-plan Routes
    Route::prefix('fee_plan')->group(function () {
        Route::post('/view/{id?}', [FeePlanController::class, 'index'])
                ->middleware(['check-api-permission:fee.full, fee.view']); // Fetch all or one record

        Route::post('/register', [FeePlanController::class, 'register'])
                ->middleware(['check-api-permission:fee.full']); // Create a new record

        Route::post('/update/{id}', [FeePlanController::class, 'update'])
                ->middleware(['check-api-permission:fee.full']); // Update a record

        Route::delete('/{id}', [FeePlanController::class, 'destroy'])
                ->middleware(['check-api-permission:fee.full']); // Delete a record

        Route::get('/import', [FeePlanController::class, 'importCsv'])
                ->middleware(['check-api-permission:fee.full']); 
    });

    // Suppliers Routes
    Route::prefix('supplier')->group(function () {
        Route::get('/view', [SupplierController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);          // List all Suppliers

        Route::get('/view/{id}', [SupplierController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);     // Get details of a single Suppliers

        Route::post('/', [SupplierController::class, 'register'])
                ->middleware(['check-api-permission:inventory.full']);         // Add a new Suppliers (Admin only)
        Route::post('/{id}', [SupplierController::class, 'update'])
                ->middleware(['check-api-permission:inventory.full']);     // Update a Suppliers (Admin only)

        Route::delete('/{id}', [SupplierController::class, 'destroy'])
                ->middleware(['check-api-permission:inventory.full']); // Delete a Suppliers (Admin only)

        Route::get('/import', [SupplierController::class, 'importCsv'])
                ->middleware(['check-api-permission:inventory.full']); 
    });

    // Items Routes
    Route::prefix('item')->group(function () {
        Route::get('/view', [ItemController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);          // List all Items

        Route::get('/view/{id}', [ItemController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);     // Get details of a single Items

        Route::post('/', [ItemController::class, 'register'])
                ->middleware(['check-api-permission:inventory.full']);         // Add a new Items (Admin only)

        Route::post('/{id}', [ItemController::class, 'update'])
                ->middleware(['check-api-permission:inventory.full']);     // Update a Items (Admin only)

        Route::delete('/{id}', [ItemController::class, 'destroy'])
                ->middleware(['check-api-permission:inventory.full']); // Delete a Items (Admin only)

        Route::get('/import', [ItemController::class, 'importCsv'])
                ->middleware(['check-api-permission:inventory.full']); 
    });

     // Purchase Routes
     Route::prefix('attendance')->group(function () {
        Route::get('/view', [AttendanceController::class, 'index'])
                ->middleware(['check-api-permission:report.full, report.view']);          // List all Purchase

        Route::get('/view/{id}', [AttendanceController::class, 'index'])
                ->middleware(['check-api-permission:report.full, report.view']);     // Get details of a single Purchase

        Route::post('/', [AttendanceController::class, 'registerandUpdate'])
                ->middleware(['check-api-permission:report.full']);         // Add a new Purchase (Admin only)

        // Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Purchase (Admin only)
        // Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [AttendanceController::class, 'importCsv'])
                ->middleware(['check-api-permission:report.full']); 
    });
    // Purchase Routes
    Route::prefix('purchase')->group(function () {
        Route::get('/view', [PurchaseController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);          // List all Purchase

        Route::get('/view/{id}', [PurchaseController::class, 'index'])
                ->middleware(['check-api-permission:inventory.full, inventory.view']);     // Get details of a single Purchase

        Route::post('/', [PurchaseController::class, 'register'])
                ->middleware(['check-api-permission:inventory.full']);         // Add a new Purchase (Admin only)

        Route::post('/{id}', [PurchaseController::class, 'update'])
                ->middleware(['check-api-permission:inventory.full']);     // Update a Purchase (Admin only)

        Route::delete('/{id}', [PurchaseController::class, 'destroy'])
                ->middleware(['check-api-permission:inventory.full']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [PurchaseController::class, 'importCsv'])
                ->middleware(['check-api-permission:inventory.full']); 
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
        Route::get('/view', [MarksController::class, 'index'])
                ->middleware(['check-api-permission:report.full, report.view']);          // List all Marks

        Route::get('/view/{id}', [MarksController::class, 'index'])
                ->middleware(['check-api-permission:report.full, report.view']);     // Get details of a single Marks

        Route::post('/', [MarksController::class, 'registerandUpdate'])
                ->middleware(['check-api-permission:report.full']);         // Add a new Marks (Admin only)
                
        // Route::post('/{id}', [PurchaseController::class, 'update']);     // Update a Marks (Admin only)
        // Route::delete('/{id}', [PurchaseController::class, 'destroy']); // Delete a Marks (Admin only)
    
        Route::get('/import', [MarksController::class, 'importCsv'])
                ->middleware(['check-api-permission:report.full']); 
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
        Route::get('/view', [CharacterCertificateController::class, 'index'])
                ->middleware(['check-api-permission:students.full, students.view']);          // List all Purchase

        Route::post('/register/{id?}', [CharacterCertificateController::class, 'storeOrUpdate'])
                ->middleware(['check-api-permission:students.full']);         // Add a new Purchase (Admin only)

         Route::post('/details', [CharacterCertificateController::class, 'getDetails'])
                ->middleware(['check-api-permission:students.full']);       

        Route::post('/export', [CharacterCertificateController::class, 'export'])
                ->middleware(['check-api-permission:students.full']); 


        Route::delete('/{id}', [CharacterCertificateController::class, 'destroy'])
                ->middleware(['check-api-permission:students.full']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [CharacterCertificateController::class, 'importCsv'])
                ->middleware(['check-api-permission:students.full']); 

        Route::post('/bulk', [CharacterCertificateController::class, 'bulk'])
                ->middleware(['check-api-permission:students.full']); 

        Route::get('/print/{id}', [CharacterCertificateController::class, 'printPdf'])
                ->middleware(['check-api-permission:students.full']); 
    });

    // Character Certificate Routes
    Route::prefix('transfer_certificate')->group(function () {
        Route::post('/view', [TransferCertificateController::class, 'index'])
                ->middleware(['check-api-permission:students.full, students.view']);          // List all Purchase
                
        Route::post('/student-details', [TransferCertificateController::class, 'getStudentDetails'])
                ->middleware(['check-api-permission:students.full']);       

        Route::post('/details', [TransferCertificateController::class, 'getDetails'])
                ->middleware(['check-api-permission:students.full']);       

         Route::post('/export', [TransferCertificateController::class, 'export'])
                ->middleware(['check-api-permission:students.full']); 


        Route::post('/{id?}', [TransferCertificateController::class, 'storeOrUpdate'])
                ->middleware(['check-api-permission:students.full']);         // Add a new Purchase (Admin only)

        Route::delete('/{id}', [TransferCertificateController::class, 'destroy'])
                ->middleware(['check-api-permission:students.full']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [TransferCertificateController::class, 'importCsv'])
                ->middleware(['check-api-permission:students.full']); 
        
        Route::get('/print/{id}', [TransferCertificateController::class, 'printPdf'])
                ->middleware(['check-api-permission:students.full']); 
        
        
    });

    // New Admission Routes
    Route::prefix('new_admission')->group(function () {
        Route::get('/view', [NewAdmissionController::class, 'view'])
                ->middleware(['check-api-permission:new_admissions.full, new_admissions.view']);          // List all Admissions

        Route::post('/register', [NewAdmissionController::class, 'register'])
                ->middleware(['check-api-permission:new_admissions.full']); // Register a specific child

        Route::post('/update/{id}', [NewAdmissionController::class, 'update'])
                ->middleware(['check-api-permission:new_admissions.full']); // Update a specific child

        Route::delete('/{id}', [NewAdmissionController::class, 'destroy'])
                ->middleware(['check-api-permission:new_admissions.full']); // Delete a Purchase (Admin only)
    
        Route::get('/import', [NewAdmissionController::class, 'importCsv'])
                ->middleware(['check-api-permission:new_admissions.full']); 
    });

    // Teacher Application Routes
    Route::prefix('teacher_application')->group(function () {
        Route::get('/view', [TeacherApplicationController::class, 'view'])
                ->middleware(['check-api-permission:teacher_applications.full']);          // List all Application

        Route::post('/register', [TeacherApplicationController::class, 'register'])
                ->middleware(['check-api-permission:teacher_applications.full']); // Register a specific application

        Route::post('/update/{id}', [TeacherApplicationController::class, 'update'])
                ->middleware(['check-api-permission:teacher_applications.full']); // Update a specific application

        Route::delete('/{id}', [TeacherApplicationController::class, 'destroy'])
                ->middleware(['check-api-permission:teacher_applications.full']); // Delete an Application (Admin only)
    
    });
    Route::get('/import_pg_response', [PGResponseController::class, 'importCsv'])
            ->middleware(['check-api-permission:accounts.full']);

    Route::prefix('transactions')->group(function () {

        Route::get('/records', [DailyTransactionController::class, 'index'])
                ->middleware(['check-api-permission:accounts.full, accounts.view']);
        
                Route::get('/modes', [DailyTransactionController::class, 'getDistinctPaymentModes'])
                ->middleware(['check-api-permission:accounts.full, accounts.view']);

        Route::get('/export', [DailyTransactionController::class, 'exportToExcel'])
                ->middleware(['check-api-permission:accounts.full, accounts.view']);

    });

    Route::get('/import_txn', [TransactionController::class, 'importCsv'])
            ->middleware(['check-api-permission:accounts.full']);

    Route::get('/import_txn_type', [TransactionTypeController::class, 'importCsv'])
            ->middleware(['check-api-permission:accounts.full']);

    Route::get('/fetch_txns', [TransactionTypeController::class, 'fetchTransactions'])
            ->middleware(['check-api-permission:accounts.full, accounts.view']);

    // For Test, not associate to others
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

    Route::get('/blood-groups', [UserController::class, 'getBloodGroups']);

// Route for fetching house options
    Route::get('/house-options', [UserController::class, 'getHouseOptions']);

    });
