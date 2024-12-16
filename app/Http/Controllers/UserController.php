<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
}
