<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordUpdated;
use Hash;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    //
     //register admin
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) use ($request) {
                    // Check if the combination of email already exists
                    $exists = \App\Models\User::where('email', $value)
                                            ->exists();
                    if ($exists) {
                        $fail('The email must be unique.');
                    }
                },
            ],
            'password' => 'required|string',
            'username' => 'required|string' 
        ]);

        $register_admin = User::create([
            'name' => $request->input('name'),
            'email' => strtolower($request->input('email')),
            'password' => bcrypt($request->input('password')),
            'role' => 'admin',
            'username' => $request->input('username'),
        ]);
        
        unset($register_admin['id'], $register_admin['created_at'], $register_admin['updated_at']);

        return isset($register_admin) && $register_admin !== null
        ? response()->json(['Admin registered successfully!', 'data' => $register_admin], 201)
        : response()->json(['Failed to register admin'], 400);
    }

    // update password
    public function change_password(Request $request)
    {
        $request->validate([
            'username' => 'required'
        ]);

        $fetch_user = User::where('username', $request->input('username'))->first();
        
        if (isset($fetch_user)) {
            
            if ($fetch_user->email)
            {
                // Generate a random 6-digit integer password
                $updated_password = random_int(100000, 999999);

                // Send an email to the user's email address
                try {
                    Mail::to($fetch_user->email)->send(new PasswordUpdated($updated_password));

                    // Retrieve the user by username
                    $update_user = User::where('username', $request->input('username'))->firstOrFail();

                    $update_user->update([
                        'password' => bcrypt($updated_password),
                    ]);

                    return response()->json(['message' => 'Email sent successfully.', 'status' => 'true'], 200);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Failed to send email.', 'error' => $e->getMessage()], 500);
                }
            } else {
                // If the email is not present
                return response()->json([
                    'message' => 'Email is not present.',
                    'status' => 'false'
                ], 200);
            }
        }

        else{
             // If the user does not exist
            return response()->json(['message' => 'User not found.', 'status' => 'false'], 200);
        }
    }

    public function updatePassword(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'new_password' => 'required|string|min:8|confirmed', // 'confirmed' ensures it matches 'new_password_confirmation'
        ]);

        // Update the password
        $user = auth()->user(); // Get the currently authenticated user
        $user->password = Hash::make($validated['new_password']); // Hash the new password
        $user->save(); // Save the updated user model

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully!',
        ]);
    }
    public function getBloodGroups()
    {
        try {
            // Fetch enum values for the st_blood_group column
            $result = DB::select("SHOW COLUMNS FROM t_students WHERE Field = 'st_blood_group'");
    
            // Extract enum values
            $bloodGroups = [];
            if (!empty($result)) {
                $type = $result[0]->Type; // Get the column type (e.g., enum('A+', 'A-', ...))
                preg_match("/^enum\('(.*)'\)$/", $type, $matches);
                $bloodGroups = isset($matches[1]) ? explode("','", $matches[1]) : [];
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Blood groups fetched successfully',
                'data' => $bloodGroups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching blood groups',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getHouseOptions()
    {
        try {
            // Fetch enum values for the st_house column
            $result = DB::select("SHOW COLUMNS FROM t_students WHERE Field = 'st_house'");
    
            // Extract enum values
            $houses = [];
            if (!empty($result)) {
                $type = $result[0]->Type; // Get the column type (e.g., enum('red', 'blue', ...))
                preg_match("/^enum\('(.*)'\)$/", $type, $matches);
                $houses = isset($matches[1]) ? explode("','", $matches[1]) : [];
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'House options fetched successfully',
                'data' => $houses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching house options',
                'error' => $e->getMessage()
            ]);
        }
    }
}
