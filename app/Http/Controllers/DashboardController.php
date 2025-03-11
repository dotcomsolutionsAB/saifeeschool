<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use App\Models\FeeModel;

class DashboardController extends Controller
{
    //
    public function dashboard()
    {
        try {
            // ✅ Get current month and year from the server
            $currentMonth = now()->month;
            $currentYear = now()->year;
    
            // ✅ Fetch the current academic year ID (`ay_id`) where `is_current = '1'`
            $currentAcademicYear = DB::table('t_academic_years')
                ->where('ay_current', '1')
                ->value('id');
    
            // ✅ If no current academic year is found, return an error response
            if (!$currentAcademicYear) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active academic year found',
                ], 400);
            }
    
            // ✅ Fetch dashboard stats
            $getTeacherNumber = TeacherModel::count();
            $getStudentNumber = StudentModel::where('ay_id', $currentAcademicYear)->count();
            $maleCount = StudentModel::where('ay_id', $currentAcademicYear)->where('st_gender', 'M')->count();
            $femaleCount = StudentModel::where('ay_id', $currentAcademicYear)->where('st_gender', 'F')->count();
            $totalAmount = FeeModel::where('f_paid', '0')->sum('fpp_amount');
            $totalLateFeesPaid = FeeModel::where('f_late_fee_applicable', '1')->sum('f_total_paid');
            $currentMonthAmount = FeeModel::where('f_paid', '0')
                                          ->where('fpp_month_no', $currentMonth)
                                          ->where('fpp_year_no', $currentYear)
                                          ->sum('fpp_amount');
    
            // ✅ Return a successful response
            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'current_ay_id' => $currentAcademicYear,
                    'teacher_count' => $getTeacherNumber,
                    'student_count' => $getStudentNumber,
                    'male_student_count' => $maleCount,
                    'female_student_count' => $femaleCount,
                    'total_unpaid_amount' => $totalAmount,
                    'total_late_fees_paid' => $totalLateFeesPaid,
                    'current_month_unpaid_amount' => $currentMonthAmount,
                ],
            ], 200);
    
        } catch (\Exception $e) {
            // ❌ Return an error response in case of an exception
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFeeBreakdown(Request $request)
{
    try {
        // ✅ Get Current Academic Year ID
        $currentAcademicYear = DB::table('t_academic_years')
            ->where('ay_current', '1')
            ->value('id');

        if (!$currentAcademicYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active academic year found',
            ], 400);
        }

        // ✅ Fetch One-Time Fees (Non-Recurring)
        $oneTimeFees = FeeModel::where('ay_id', $currentAcademicYear)
            ->where('fp_recurring', '0')
            ->selectRaw('SUM(fpp_amount) as total_amount, SUM(f_total_paid) as fee_paid, SUM(fpp_amount - f_total_paid) as fee_due, SUM(f_late_fee_paid) as late_fee_collected')
            ->first();

        // ✅ Fetch Admission Fees
        $admissionFees = FeeModel::where('ay_id', $currentAcademicYear)
            ->where('fp_main_admission_fee', '1')
            ->selectRaw('SUM(fpp_amount) as total_amount, SUM(f_total_paid) as fee_paid, SUM(fpp_amount - f_total_paid) as fee_due, SUM(f_late_fee_paid) as late_fee_collected')
            ->first();

        // ✅ Fetch Monthly Fees (Grouped by Month)
        $monthlyFees = FeeModel::where('ay_id', $currentAcademicYear)
            ->where('fp_main_monthly_fee', '1')
            ->groupBy('fpp_month_no')
            ->orderBy('fpp_month_no')
            ->selectRaw('fpp_month_no, SUM(fpp_amount) as total_amount, SUM(f_total_paid) as fee_paid, SUM(fpp_amount - f_total_paid) as fee_due, SUM(f_late_fee_paid) as late_fee_collected')
            ->get();

        // ✅ Format Response
        return response()->json([
            'status' => 'success',
            'message' => 'Fee breakdown retrieved successfully',
            'data' => [
                'one_time_fees' => [
                    'total_amount' => $oneTimeFees->total_amount ?? 0,
                    'fee_paid' => $oneTimeFees->fee_paid ?? 0,
                    'fee_due' => $oneTimeFees->fee_due ?? 0,
                    'late_fee_collected' => $oneTimeFees->late_fee_collected ?? 0,
                ],
                'admission_fees' => [
                    'total_amount' => $admissionFees->total_amount ?? 0,
                    'fee_paid' => $admissionFees->fee_paid ?? 0,
                    'fee_due' => $admissionFees->fee_due ?? 0,
                    'late_fee_collected' => $admissionFees->late_fee_collected ?? 0,
                ],
                'monthly_fees' => $monthlyFees->map(function ($month) {
                    return [
                        'month_no' => $month->fpp_month_no,
                        'total_amount' => $month->total_amount,
                        'fee_paid' => $month->fee_paid,
                        'fee_due' => $month->fee_due,
                        'late_fee_collected' => $month->late_fee_collected,
                    ];
                }),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve fee breakdown',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}