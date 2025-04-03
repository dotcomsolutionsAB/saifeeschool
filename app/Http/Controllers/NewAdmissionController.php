<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\NewAdmissionModel; // New admission model for the table
use App\Models\StudentModel; // Student model for verification
use App\Models\UploadModel; // Upload model for saving files
use Str; // For generating random strings

class NewAdmissionController extends Controller
{
    public function register(Request $request)
    {
        // Base validation for general fields
        $validated = Validator::make($request->all(), [
            // Personal details
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:m,f',
            'dob' => 'required|date',
            'aadhaar' => 'required|digits:12|unique:new_admissions,aadhaar_no',
            'residential_address1' => 'required|string|max:1000',
            'residential_address2' => 'nullable|string|max:1000',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'pincode' => 'required|digits:6',
            'country' => 'required|string|max:255',
            'last_school' => 'required|string|max:1000',
            'last_school_address' => 'required|string|max:1000',
            'father_name' => 'required|string|max:255',
            'father_surname' => 'required|string|max:255',
            'father_education' => 'nullable|string|max:255',
            'father_occupation' => 'required|in:business,employed,no-occupation',
            'father_mobile' => 'required|string|max:20',
            'father_email' => 'required|email|max:255',
            'father_monthly_income' => 'nullable|string|max:255',
            'mother_first_name' => 'required|string|max:255',
            'mother_last_name' => 'required|string|max:255',
            'mother_education' => 'nullable|string|max:255',
            'mother_occupation' => 'required|in:business,employed,housewife,not-applicable',
            'mother_mobile' => 'required|string|max:20',
            'mother_email' => 'required|email|max:255',
            'mother_monthly_income' => 'nullable|string|max:255',
            'siblings' => 'nullable|array|max:3',
            'siblings.*.cg_id' => 'required|exists:t_class_groups,id',
            'siblings.*.roll_no' => 'required|string|max:255|exists:t_students,st_roll_no',
            'attracted' => 'nullable|string',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'remarks' => 'nullable|string',
            // File uploads
            'child_photo' => 'required|image|max:2048',
            'father_photo' => 'required|image|max:2048',
            'mother_photo' => 'required|image|max:2048',
            'birth_certificate' => 'required|file|max:2048',
        ])->validate();

        // Father's occupation related validation
        if ($validated['father_occupation'] == 'business') {
            $validated = array_merge($validated, Validator::make($request->all(), [
                'father_business_name' => 'required|string|max:255',
                'father_business_nature' => 'required|string|max:255',
                'father_business_address' => 'required|string|max:255',
                'father_business_city' => 'required|string|max:255',
                'father_business_state' => 'required|string|max:255',
                'father_business_country' => 'required|string|max:255',
                'father_business_pincode' => 'required|string|max:10',
            ])->validate());
        } elseif ($validated['father_occupation'] == 'employed') {
            $validated = array_merge($validated, Validator::make($request->all(), [
                'father_employer_name' => 'required|string|max:255',
                'father_designation' => 'required|string|max:255',
                'father_work_address' => 'required|string|max:255',
                'father_work_city' => 'required|string|max:255',
                'father_work_state' => 'required|string|max:255',
                'father_work_country' => 'required|string|max:255',
                'father_work_pincode' => 'required|string|max:10',
            ])->validate());
        }

        // Mother's occupation related validation
        if ($validated['mother_occupation'] == 'business') {
            $validated = array_merge($validated, Validator::make($request->all(), [
                'mother_business_name' => 'required|string|max:255',
                'mother_business_nature' => 'required|string|max:255',
                'mother_business_address' => 'required|string|max:255',
                'mother_business_city' => 'required|string|max:255',
                'mother_business_state' => 'required|string|max:255',
                'mother_business_country' => 'required|string|max:255',
                'mother_business_pincode' => 'required|string|max:10',
            ])->validate());
        } elseif ($validated['mother_occupation'] == 'employed') {
            $validated = array_merge($validated, Validator::make($request->all(), [
                'mother_employer_name' => 'required|string|max:255',
                'mother_designation' => 'required|string|max:255',
                'mother_work_address' => 'required|string|max:255',
                'mother_work_city' => 'required|string|max:255',
                'mother_work_state' => 'required|string|max:255',
                'mother_work_country' => 'required|string|max:255',
                'mother_work_pincode' => 'required|string|max:10',
            ])->validate());
        }

        try {
            // Check if the Aadhaar number already exists in the student database
            $existingStudent = StudentModel::where('aadhaar_no', $validated['aadhaar'])->first();
            if ($existingStudent) {
                return response()->json([
                    'message' => 'A student with this Aadhaar number already exists.',
                ], 400);
            }

            // Generate a unique application number
            $applicationNo = strtoupper(Str::random(10));

            // Create a new admission record
            $newAdmission = NewAdmissionModel::create([
                'application_no' => $applicationNo,
                'ay_id' => 1, // Assuming the current academic year ID is 1
                'class' => $validated['class'], // Class should be passed in the request
                'date' => now(),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['dob'],
                'last_school' => $validated['last_school'],
                'last_school_address' => $validated['last_school_address'],
                'aadhaar_no' => $validated['aadhaar'],
                'father_name' => $validated['father_name'],
                'father_surname' => $validated['father_surname'],
                'father_education' => $validated['father_education'] ?? null,
                'father_occupation' => $validated['father_occupation'],
                'father_employer' => $validated['father_employer_name'] ?? null,
                'father_designation' => $validated['father_designation'] ?? null,
                'father_business' => $validated['father_business_name'] ?? null,
                'father_business_nature' => $validated['father_business_nature'] ?? null,
                'father_business_address' => $validated['father_business_address'] ?? null,
                'father_mobile' => $validated['father_mobile'],
                'father_email' => $validated['father_email'],
                'father_monthly_income' => $validated['father_monthly_income'] ?? null,
                'mother_first_name' => $validated['mother_first_name'],
                'mother_last_name' => $validated['mother_last_name'],
                'mother_name' => $validated['mother_first_name'] . ' ' . $validated['mother_last_name'],
                'mother_education' => $validated['mother_education'] ?? null,
                'mother_occupation' => $validated['mother_occupation'],
                'mother_employer' => $validated['mother_employer_name'] ?? null,
                'mother_designation' => $validated['mother_designation'] ?? null,
                'mother_business' => $validated['mother_business_name'] ?? null,
                'mother_business_nature' => $validated['mother_business_nature'] ?? null,
                'mother_business_address' => $validated['mother_business_address'] ?? null,
                'mother_mobile' => $validated['mother_mobile'],
                'mother_email' => $validated['mother_email'],
                'mother_monthly_income' => $validated['mother_monthly_income'] ?? null,
                'siblings' => json_encode($validated['siblings'] ?? []),
                'attracted' => $validated['attracted'] ?? null,
                'strengths' => $validated['strengths'] ?? null,
                'weaknesses' => $validated['weaknesses'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save uploaded files
            $files = [
                'child_photo' => $validated['child_photo'],
                'father_photo' => $validated['father_photo'],
                'mother_photo' => $validated['mother_photo'],
                'birth_certificate' => $validated['birth_certificate']
            ];

            $filePaths = [];
            foreach ($files as $key => $file) {
                $fileName = $key . "_" . time() . "." . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('uploads/admissions/' . $applicationNo, $fileName, 'public');
                $filePaths[$key] = Storage::url($filePath);
            }

            // Save file details into the upload model
            UploadModel::create([
                'st_id' => $newAdmission->id,
                'file_name' => json_encode($filePaths), // Save all file paths as JSON
                'file_url' => json_encode($filePaths),
                'file_size' => json_encode(array_map(fn($file) => $file->getSize(), $files)),
            ]);

            return response()->json([
                'message' => 'New admission registered successfully.',
                'data' => $newAdmission,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}