<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AcademicYearModel;

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

        if(Auth::attempt(['username' => $request->username, 'password' => $request->password]))
        {
            $user = Auth::user();

            // Generate a sanctrum token
            $generated_token = $user->createToken('API TOKEN')->plainTextToken;

            $get_current_year = AcademicYearModel::select('id')->where('ay_current', 1)->first();

            if ($get_current_year == null) {
                $current_year_id = 1;
            }
            else {
                $current_year_id = $get_current_year->id;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $generated_token,
                    'name' => $user->name,
                    'role' => $user->role,
                    'current_year_id' => $current_year_id,
                ],
                'message' => 'User logged in successfully!',
            ], 200);
        }

        else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password!',
            ], 401);
        }
    }

    // user `logout`
    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if(!$request->user()) {
            return response()->json([
                'success'=> false,
                'message'=>'Sorry, no user is logged in now!',
            ], 401);
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully!',
        ], 204);
    }
}
