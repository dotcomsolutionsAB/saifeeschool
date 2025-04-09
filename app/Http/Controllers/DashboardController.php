<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use App\Models\FeeModel;
use App\Models\AcademicYearModel;

class DashboardController extends Controller
{
    //
   public function dashboard(Request $request)
{
    try {
        $validated = $request->validate([
            'ay_id' => 'required|integer|exists:t_academic_years,id',
        ]);

        // ✅ Get the selected academic year ID
        $currentAcademicYear = $validated['ay_id'];
        $academicYear = AcademicYearModel::find($currentAcademicYear);

        // ✅ Get current month and year
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // ✅ Fetch Total Teachers
        $getTeacherNumber = TeacherModel::count();

        // ✅ Fetch Student Count using `t_student_classes`
        $getStudentNumber = DB::table('t_student_classes')
            ->where('ay_id', $currentAcademicYear)
            ->count();

        // ✅ Fetch Male Students
        $maleCount = DB::table('t_student_classes')
            ->join('t_students', 't_students.id', '=', 't_student_classes.st_id')
            ->where('t_student_classes.ay_id', $currentAcademicYear)
            ->where('t_students.st_gender', 'M')
            ->count();

        // ✅ Fetch Female Students
        $femaleCount = DB::table('t_student_classes')
            ->join('t_students', 't_students.id', '=', 't_student_classes.st_id')
            ->where('t_student_classes.ay_id', $currentAcademicYear)
            ->where('t_students.st_gender', 'F')
            ->count();

            $currentMonthStart = now()->startOfMonth()->toDateString(); // e.g., 2025-03-01
$currentMonthEnd = now()->endOfMonth()->toDateString();     // e.g., 2025-03-31
        // ✅ Calculate Fee Stats
        $totalUnpaidAmount = FeeModel::where('f_paid', '0')->sum('fpp_amount');
        $totalLateFeesPaid = FeeModel::where('f_late_fee_applicable', '1')->sum('f_total_paid');
        $currentMonthUnpaidAmount = FeeModel::where('f_paid', '0')
            ->where('fpp_month_no', $currentMonth)
            ->where('fpp_year_no', $currentYear)
            ->sum('fpp_amount');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'current_ay_id' => $currentAcademicYear,
                'teacher_count' => $getTeacherNumber,
                'student_count' => $getStudentNumber,
                'male_student_count' => $maleCount,
                'female_student_count' => $femaleCount,

                'total_unpaid_fees' => [
                    'amount' => $totalUnpaidAmount,
                    'query_key' => [
                        'ay_id' => $currentAcademicYear,
                        'ay_name' => $academicYear->ay_name,
                        'status' => 'unpaid'
                    ]
                ],
               
                'current_month_unpaid_fees' => [
                    'amount' => $currentMonthUnpaidAmount,
                    'query_key' => [
                        'ay_id' => $currentAcademicYear,
                        'ay_name' => $academicYear->ay_name,
                        'month_no' => $currentMonth,
                        'status' => 'unpaid',
                        'date_from' => $currentMonthStart,
                        'date_to' => $currentMonthEnd,
    
                    ]
                ],
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code'=>500,
            'status' => 'error',
            'message' => 'Failed to retrieve dashboard data',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function studentDashboard(Request $request)
    {
        try {
            // ✅ Validate Input
            $validated = $request->validate([
                'st_id' => 'required|integer|exists:t_students,id',
            ]);
    
            // ✅ Get Student Details
            $student = DB::table('t_students')
                ->where('id', $validated['st_id'])
                ->select('id', 'st_roll_no', 'st_first_name', 'st_last_name', 'st_wallet', 'st_deposit')
                ->first();
    
            if (!$student) {
                return response()->json([
                    'code'=> 400,
                    'status' => 'error',
                    'message' => 'Student not found',
                ], 404);
            }
    
            // ✅ Get Student Class (from `t_student_classes`)
            $studentClass = DB::table('t_student_classes')
                ->join('t_class_groups', 't_student_classes.cg_id', '=', 't_class_groups.id')
                ->where('t_student_classes.st_id', $validated['st_id'])
                ->where('t_student_classes.ay_id', function ($query) {
                    $query->select('id')
                        ->from('t_academic_years')
                        ->where('ay_current', '1')
                        ->limit(1);
                })
                ->select('t_class_groups.cg_name')
                ->first();
    
            // ✅ Format Response
            return response()->json([
                'code'=> 200,
                'status' => 'success',
                'message' => 'Student dashboard data retrieved successfully',
                'data' => [
                    'st_id' => $student->id,
                    'roll_no' => $student->st_roll_no,
                    'name' => $student->st_first_name . ' ' . $student->st_last_name,
                    'class' => $studentClass->cg_name ?? 'Not Assigned',
                    'st_wallet' => $student->st_wallet ?? 0,
                    'st_deposit' => $student->st_deposit ?? 0,
                ],
            ], 200);
    
        } catch (\Exception $e) {
            // ❌ Error Handling
            return response()->json([
                'code'=> 500,
                'status' => 'error',
                'message' => 'Failed to retrieve student dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFeeBreakdown(Request $request)
    {
        try {
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
            ]);
    
            $currentAcademicYear = $validated['ay_id'];
            $academicYear = AcademicYearModel::find($currentAcademicYear);
    
            // Fetch One-Time Fees
            $oneTimeFees = FeeModel::where('ay_id', $currentAcademicYear)
                ->where('fp_recurring', '0')
                ->selectRaw('SUM(fpp_amount) as total_amount, SUM(f_total_paid) as fee_paid, SUM(fpp_amount - f_total_paid) as fee_due, SUM(f_late_fee_paid) as late_fee_collected')
                ->first();
    
            // Fetch Admission Fees
            $admissionFees = FeeModel::where('ay_id', $currentAcademicYear)
                ->where('fp_main_admission_fee', '1')
                ->where('f_active', '1')
                ->selectRaw('SUM(fpp_amount) as total_amount, SUM(f_total_paid) as fee_paid, SUM(fpp_amount - f_total_paid) as fee_due, SUM(f_late_fee_paid) as late_fee_collected')
                ->first();
    
            // Fetch Monthly Fees
            $monthlyFees = FeeModel::where('ay_id', $currentAcademicYear)
                ->where('fp_main_monthly_fee', '1')
                ->where('fp_recurring', '1')
                ->where('f_active', '1')
                ->get();
    
            // Group monthly fees by Month-Year from due_date
            $groupedMonthlyFees = $monthlyFees->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->fpp_due_date)->format('F Y');
            });
    
            $formattedMonthlyFees = $groupedMonthlyFees->map(function ($items, $monthYear)use ($academicYear) {
                $totalAmount = $items->sum('fpp_amount');
                $paidAmount = $items->sum('f_total_paid');
                $dueAmount = $totalAmount - $paidAmount;
                $lateFee = $items->sum('f_late_fee_paid');
    
                return [
                    'month_name' => $monthYear,
                    'total_amount' => $totalAmount,
                    'fee_paid' => $paidAmount,
                    'fee_due' => $dueAmount,
                    'late_fee_collected' => $lateFee,
                    
                    'query_key_unpaid' => [
                        'ay_id' => $academicYear->ay_id,
                        'ay_name' => $academicYear->ay_name,
                        'type' => 'monthly',
                        'status' => 'unpaid',
                        'date_from' => $items->min('fpp_due_date'),
                        'date_to' => $items->max('fpp_due_date'),
                    ]
                ];
            })->values(); // Reset keys
    
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Fee breakdown retrieved successfully',
                'data' => [
                    'one_time_fees' => [
                        'total_amount' => $oneTimeFees->total_amount ?? 0,
                        'fee_paid' => $oneTimeFees->fee_paid ?? 0,
                        'fee_due' => $oneTimeFees->fee_due ?? 0,
                        'late_fee_collected' => $oneTimeFees->late_fee_collected ?? 0,
                        
                        'query_key_unpaid' => [
                            'year' => $currentAcademicYear,
                            'ay_name' => $academicYear->ay_name,
                            'type' => 'one_time',
                            'status' => 'unpaid',
                        ],
                    ],
                    'admission_fees' => [
                        'total_amount' => $admissionFees->total_amount ?? 0,
                        'fee_paid' => $admissionFees->fee_paid ?? 0,
                        'fee_due' => $admissionFees->fee_due ?? 0,
                        'late_fee_collected' => $admissionFees->late_fee_collected ?? 0,
                        
                        'query_key_unpaid' => [
                            'year' => $currentAcademicYear,
                            'ay_name' => $academicYear->ay_name,
                            'type' => 'admission',
                            'status' => 'unpaid',
                        ],
                    ],
                    'monthly_fees' => $formattedMonthlyFees,
                ],
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Failed to retrieve fee breakdown',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}