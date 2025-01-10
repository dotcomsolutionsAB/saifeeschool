<?php

namespace App\Http\Controllers;

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
            // Get current month and year from the server
            $currentMonth = now()->month; // Fetches the current month (1-12)
            $currentYear = now()->year;  // Fetches the current year

            // Calculate values
            $getTeacherNumber = TeacherModel::count();
            $getStudentNumber = StudentModel::count();
            $maleCount = StudentModel::where('st_gender', 'M')->count();
            $femaleCount = StudentModel::where('st_gender', 'F')->count();
            $totalAmount = FeeModel::where('f_paid', '0')->sum('fpp_amount');
            $totalLateFeesPaid = FeeModel::where('f_late_fee_applicable', '1')->sum('f_total_paid');
            $currentMonthAmount = FeeModel::where('f_paid', '0')
                ->where('fpp_month_no', $currentMonth)
                ->where('fpp_year_no', $currentYear)
                ->sum('fpp_amount');

            // Return a successful response
            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
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
            // Return an error response in case of an exception
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
