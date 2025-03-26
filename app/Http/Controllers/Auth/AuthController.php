<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AcademicYearModel;
use Illuminate\Support\Facades\DB; 

class AuthController extends Controller
{
    //
    // user `login`
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
    
            // ✅ Generate Sanctum Token
            $generated_token = $user->createToken('API TOKEN')->plainTextToken;
    
            // ✅ Get Current Academic Year
            $get_current_year = DB::table('t_academic_years')
                ->select('id','ay_name')
                ->where('ay_current', 1)
                ->first();
    
            $current_year_id = $get_current_year->id ?? 1;
            $year_name=$get_current_year->ay_name??'NA';
    
            // ✅ If the user's role is "student", fetch `st_id` from `t_students`
            if ($user->role === 'student') {
                $student = DB::table('t_students')
                    ->select('id')
                    ->where('st_roll_no', $user->username)
                    ->first();
    
                if (!$student) {
                    return response()->json([
                        'code' => 404,
                        'status' => false,
                        'message' => 'Student not found for the given username!',
                    ], 404);
                }
    
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Student logged in successfully!',
                    'data' => [
                        'token' => $generated_token,
                        'name' => $user->name,
                        'role' => $user->role,
                        'st_id' => $student->id, // ✅ Returning `st_id`
                        'ay_id' => $current_year_id,
                        'ay_name'=>$year_name,
                    ],
                ], 200);
            }
    
            // ✅ If user is NOT a student, return regular response
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'User logged in successfully!',
                'data' => [
                    'token' => $generated_token,
                    'name' => $user->name,
                    'role' => $user->role,
                    'ay_id' => $current_year_id,
                    'ay_name'=>$year_name,
                ],
            ], 200);
        }
    
        // ❌ Invalid Login Response
        return response()->json([
            'code' => 401,
            'status' => false,
            'message' => 'Invalid username or password!',
        ], 401);
    }
    // user `logout`
    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if(!$request->user()) {
            return response()->json([
                'code' => 200,
                'success'=> false,
                'message'=>'Sorry, no user is logged in now!',
            ], 200);
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'Logged out successfully!',
        ], 200);
    }
}
