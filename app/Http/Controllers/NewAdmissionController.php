<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\NewAdmissionModel; // New admission model for the table
use App\Models\StudentModel; // Student model for verification
use App\Models\UploadModel; // Upload model for saving files
 // For generating random strings
use Illuminate\Support\Str;

class NewAdmissionController extends Controller
{
    public function register(Request $request)
    {
        // Base validation for general fields
        $jsonData = json_decode($request->input('json_data'), true);

        // Validate the decoded JSON data
        $validated = Validator::make($jsonData, [
            // Personal details
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'class' => 'reqiured|string|max:10',
            'gender' => 'required|in:m,f',
            'dob' => 'required|date',
            'aadhaar' => 'required|digits:12|unique:t_new_admission,aadhaar_no',
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
                'trace' => $e->getTraceAsString(), // helpful for local dev
    'line' => $e->getLine(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function registerAdmission(Request $request)
    {
        try {
            // Use the incoming JSON request directly
            $jsonData = json_decode($request->input('json_data'), true);
    
            // Validate the decoded JSON data
            $validated = Validator::make($jsonData, [
                // General fields
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'ay_id' => 'required|integer',
                'class' => 'required|string|max:100',
                'gender' => 'required|in:m,f',
                'dob' => 'required|date',
                'aadhaar' => 'nullable|digits:12|unique:t_new_admission,aadhaar_no',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'pincode' => 'required|digits:6',
                'country' => 'required|string|max:255',
                'last_school' => 'required|string|max:1000',
                'last_school_address' => 'required|string|max:1000',
                'father_name' => 'required|string|max:255',
                'father_surname' => 'required|string|max:255',
                'father_occupation' => 'required|in:business,employed,no-occupation',
                'father_mobile' => 'required|string|max:20',
                'father_email' => 'required|email|max:255',
                'father_monthly_income' => 'nullable|string|max:255',
                'mother_first_name' => 'required|string|max:255',
                'mother_last_name' => 'required|string|max:255',
                'mother_occupation' => 'required|in:business,employed,housewife,not-applicable',
                'mother_mobile' => 'required|string|max:20',
                'mother_email' => 'required|email|max:255',
                'mother_monthly_income' => 'nullable|string|max:255',
                'siblings' => 'nullable|array|max:3',
                'siblings.*.cg_id' => 'required|exists:t_class_groups,id',
                'siblings.*.roll_no' => 'required|string|max:255|exists:t_students,st_roll_no',
                'address_1' => 'required|string|max:1000',
                'address_2' => 'nullable|string|max:1000',
                'attracted' => 'nullable|string',
                'strengths' => 'nullable|string',
                'remarks' => 'nullable|string',
                'interview_status' => 'nullable|in:0,1',
                'added_to_school' => 'nullable|in:0,1',
                'comments' => 'nullable|string',
                'printed' => 'nullable|in:0,1',
            ])->validate();
    
            // Handle occupation-based validation (father and mother)
            $validated = $this->handleOccupationValidation($validated, $jsonData);
    
            // Generate unique application number
            $applicationNo = strtoupper(Str::random(10));
    
            // Create new admission record and save the occupation-related details
            $newAdmission = NewAdmissionModel::create([
                'application_no' => $applicationNo,
                'ay_id' => $validated['ay_id'],
                'class' => $validated['class'],
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
                'father_occupation' => $validated['father_occupation'],
                'father_employer' => $validated['father_employer_name'] ?? null,
                'father_designation' => $validated['father_designation'] ?? null,
                'father_business' => $validated['father_business_name'] ?? null,
                'father_business_nature' => $validated['father_business_nature'] ?? null,
                'father_monthly_income' => $validated['father_monthly_income'] ?? null,
                'father_mobile' => $validated['father_mobile'],
                'father_email' => $validated['father_email'],
                'father_work_business_address' => $validated['father_work_business_address'] ?? null,
                'mother_first_name' => $validated['mother_first_name'],
                'mother_last_name' => $validated['mother_last_name'],
                'mother_name' => $validated['mother_first_name'] . ' ' . $validated['mother_last_name'],
                'mother_education' => $validated['mother_education'] ?? null,
                'mother_occupation' => $validated['mother_occupation'],
                'mother_employer' => $validated['mother_employer_name'] ?? null,
                'mother_designation' => $validated['mother_designation'] ?? null,
                'mother_business' => $validated['mother_business_name'] ?? null,
                'mother_business_nature' => $validated['mother_business_nature'] ?? null,
                'mother_monthly_income' => $validated['mother_monthly_income'] ?? null,
                'mother_mobile' => $validated['mother_mobile'],
                'mother_email' => $validated['mother_email'],
                'mother_work_business_address' => $validated['mother_work_business_address'] ?? null,
                'siblings_name1' => $validated['siblings'][0]['name'] ?? null,
                'siblings_class1' => $validated['siblings'][0]['class'] ?? null,
                'siblings_roll_no1' => $validated['siblings'][0]['roll_no'] ?? null,
                'address_1' => $validated['address_1'],
                'address_2' => $validated['address_2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'country' => $validated['country'],
                'pincode' => $validated['pincode'],
                'attracted' => $validated['attracted'] ?? null,
                'strengths' => $validated['strengths'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'ad_paid' => '0', // Assuming the ad fee is not paid initially
                'transaction_id' => null, // No transaction yet
                'transaction_date' => null, // No transaction yet
                'interview_date' => null, // No interview scheduled yet
                'interview_status' => '0', // Default to "Not cleared"
                'added_to_school' => '0', // Not added to school yet
                'comments' => $validated['comments'] ?? null,
                'printed' => '0', // Default to "Not printed"
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            // Proceed with file uploads after admission is saved
            $st_id = $newAdmission->id;
            return $this->uploadFiles($request, $st_id, $newAdmission);
    
        } catch (\Exception $e) {
            return response()->json([
                'code'=>'500',
                'message' => 'Registration failed',
                'trace' => $e->getTraceAsString(), // helpful for local dev
    'line' => $e->getLine(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function handleOccupationValidation($validated, $jsonData)
{
    // Father's occupation validation
    if ($validated['father_occupation'] == 'business') {
        $validated = array_merge($validated, Validator::make($jsonData, [
            'father_business_name' => 'required|string|max:255',
            'father_business_nature' => 'required|string|max:255',
            'father_business_address' => 'required|string|max:255',
            'father_business_city' => 'required|string|max:255',
            'father_business_state' => 'required|string|max:255',
            'father_business_country' => 'required|string|max:255',
            'father_business_pincode' => 'required|string|max:10',
        ])->validate());
    } elseif ($validated['father_occupation'] == 'employed') {
        $validated = array_merge($validated, Validator::make($jsonData, [
            'father_employer_name' => 'required|string|max:255',
            'father_designation' => 'required|string|max:255',
            'father_work_address' => 'required|string|max:255',
            'father_work_city' => 'required|string|max:255',
            'father_work_state' => 'required|string|max:255',
            'father_work_country' => 'required|string|max:255',
            'father_work_pincode' => 'required|string|max:10',
        ])->validate());
    }

    // Mother's occupation validation
    if ($validated['mother_occupation'] == 'business') {
        $validated = array_merge($validated, Validator::make($jsonData, [
            'mother_business_name' => 'required|string|max:255',
            'mother_business_nature' => 'required|string|max:255',
            'mother_business_address' => 'required|string|max:255',
            'mother_business_city' => 'required|string|max:255',
            'mother_business_state' => 'required|string|max:255',
            'mother_business_country' => 'required|string|max:255',
            'mother_business_pincode' => 'required|string|max:10',
        ])->validate());
    } elseif ($validated['mother_occupation'] == 'employed') {
        $validated = array_merge($validated, Validator::make($jsonData, [
            'mother_employer_name' => 'required|string|max:255',
            'mother_designation' => 'required|string|max:255',
            'mother_work_address' => 'required|string|max:255',
            'mother_work_city' => 'required|string|max:255',
            'mother_work_state' => 'required|string|max:255',
            'mother_work_country' => 'required|string|max:255',
            'mother_work_pincode' => 'required|string|max:10',
        ])->validate());
    }

    return $validated;
}
public function uploadFiles(Request $request, $st_id, $newAdmission)
{
    try {
        // Validate file uploads
        $validated = $request->validate([
            'child_photo' => 'required|image|max:2048', 
            'father_photo' => 'required|image|max:2048',
            'mother_photo' => 'required|image|max:2048',
            'birth_certificate' => 'required|file|max:2048',
        ]);

        // Directory for storing the uploaded files
        $directory = "uploads/admissions/{$st_id}";

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // File upload logic
        $files = [
            'child_photo' => $request->file('child_photo'),
            'father_photo' => $request->file('father_photo'),
            'mother_photo' => $request->file('mother_photo'),
            'birth_certificate' => $request->file('birth_certificate'),
        ];

        // Process each file upload
        foreach ($files as $key => $file) {
            $fileName = "{$st_id}_" . time() . "_{$key}." . $file->getClientOriginalExtension();
            $filePath = $file->storeAs($directory, $fileName, 'public');

            // Save file details into the uploads table
            $upload = UploadModel::create([
                'st_id' => $st_id,
                'file_name' => $fileName,
                'file_ext' => $file->getClientOriginalExtension(),
                'file_url' => "https://saifeeschool.dotcombusiness.in/storage/$filePath",
                'file_size' => $file->getSize(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update the admission record with the file ID
            switch ($key) {
                case 'child_photo':
                    $newAdmission->child_photo_id = $upload->id;
                    break;
                case 'father_photo':
                    $newAdmission->father_photo_id = $upload->id;
                    break;
                case 'mother_photo':
                    $newAdmission->mother_photo_id = $upload->id;
                    break;
                case 'birth_certificate':
                    $newAdmission->birth_certificate_id = $upload->id;
                    break;
            }
        }

        // Save the updated admission record with the file IDs
        $newAdmission->save();

        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'Files uploaded and admission registered successfully.',
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'success' => false,
            'message' => 'File upload failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function index(Request $request)
{
    try {
        // Validate incoming request
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:10',
            'year' => 'nullable|integer',
            'ad_paid' => 'nullable|in:0,1',
            'interview_status' => 'nullable|in:0,1',
            'added_to_school' => 'nullable|in:0,1',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ]);

        // Initialize query builder
        $query = NewAdmissionModel::query();

        // Apply filters based on the validated request
        if (!empty($validated['search'])) {
            $query->where(function ($query) use ($validated) {
                $query->where('first_name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('last_name', 'like', '%' . $validated['search'] . '%');
            });
        }

        if (!empty($validated['class'])) {
            $query->where('class', $validated['class']);
        }

        if (!empty($validated['year'])) {
            $query->where('ay_id', $validated['year']);
        }

        if (!empty($validated['ad_paid'])) {
            $query->where('ad_paid', $validated['ad_paid']);
        }

        if (!empty($validated['interview_status'])) {
            $query->where('interview_status', $validated['interview_status']);
        }

        if (!empty($validated['added_to_school'])) {
            $query->where('added_to_school', $validated['added_to_school']);
        }

        // Pagination (limit & offset)
        $limit = $validated['limit'] ?? 10;  // Default to 10 if not provided
        $offset = $validated['offset'] ?? 0; // Default to 0 if not provided

        $totalEntries = $query->count(); // Get the total entries before applying limit and offset

        // Select specific fields from the database
        $admissions = $query->select('id', 'application_no', 'first_name', 'last_name', 'gender', 'date_of_birth', 'class', 'ad_paid', 'interview_status', 'added_to_school', 'child_photo_id')
                            ->offset($offset)
                            ->limit($limit)
                            ->get();

        // Format the data
        $formattedAdmissions = $admissions->map(function ($admission,$index) {
            // Get the child photo URL from the uploads table
            $childPhotoUrl = $admission->child_photo_id ? UploadModel::find($admission->child_photo_id)->file_url : null;

            return [
                'sn'=>$index+1,
                'id' => $admission->id,
                'application_no' => $admission->application_no,
                'name' => $admission->first_name . ' ' . $admission->last_name,
                'gender' => $admission->gender,
                'dob' => $admission->date_of_birth,
                'class' => $admission->class,
                'ad_paid' => $admission->ad_paid,
                'interview_status' => $admission->interview_status,
                'added_to_school' => $admission->added_to_school,
                'child_photo_url' => $childPhotoUrl,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'New admissions retrieved successfully.',
            'data' => $formattedAdmissions,
            'count' => $totalEntries,
            'offset'=>$offset,
            'limit'=>$limit
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'Error retrieving admissions',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getStudentData($id)
{
    try {
        // Retrieve the admission record by student ID
        $admission = NewAdmissionModel::findOrFail($id);

        // Fetch the file URLs based on the file IDs from the uploads table
        $childPhotoUrl = $admission->child_photo_id ? UploadModel::find($admission->child_photo_id)->file_url : null;
        $fatherPhotoUrl = $admission->father_photo_id ? UploadModel::find($admission->father_photo_id)->file_url : null;
        $motherPhotoUrl = $admission->mother_photo_id ? UploadModel::find($admission->mother_photo_id)->file_url : null;
        $birthCertificateUrl = $admission->birth_certificate_id ? UploadModel::find($admission->birth_certificate_id)->file_url : null;

        $siblings = [];
        for ($i = 1; $i <= 3; $i++) {
            if ($admission["siblings_name{$i}"]) {
                $siblings[] = [
                    'name' => $admission["siblings_name{$i}"],
                    'class' => $admission["siblings_class{$i}"],
                    'roll_no' => $admission["siblings_roll_no{$i}"],
                ];
            }
        }

        // Prepare response data
        $data = [
            'id' => $admission->id,
            'application_no' => $admission->application_no,
            'first_name' => $admission->first_name,
            'last_name' => $admission->last_name,
            'gender' => $admission->gender,
            'dob' => $admission->date_of_birth,
            'class' => $admission->class,
            'ad_paid' => $admission->ad_paid,
            'interview_status' => $admission->interview_status,
            'added_to_school' => $admission->added_to_school,
            'child_photo_url' => $childPhotoUrl ? str_replace('/storage/storage', '/storage', $childPhotoUrl) : null,
            'father_photo_url' => $fatherPhotoUrl ? str_replace('/storage/storage', '/storage', $fatherPhotoUrl) : null,
            'mother_photo_url' => $motherPhotoUrl ? str_replace('/storage/storage', '/storage', $motherPhotoUrl) : null,
            'birth_certificate_url' => $birthCertificateUrl ? str_replace('/storage/storage', '/storage', $birthCertificateUrl) : null,
            'father_name' => $admission->father_name,
            'father_surname' => $admission->father_surname,
            'father_occupation' => $admission->father_occupation,
            'father_mobile' => $admission->father_mobile,
            'father_email' => $admission->father_email,
            'father_monthly_income' => $admission->father_monthly_income,
            'mother_first_name' => $admission->mother_first_name,
            'mother_last_name' => $admission->mother_last_name,
            'mother_name' => $admission->mother_name,
            'mother_education' => $admission->mother_education,
            'mother_occupation' => $admission->mother_occupation,
            'mother_mobile' => $admission->mother_mobile,
            'mother_email' => $admission->mother_email,
            'mother_monthly_income' => $admission->mother_monthly_income,
            'siblings' => $siblings,
            'address' => $admission->address_1 . ' ' . $admission->address_2,
            'city' => $admission->city,
            'state' => $admission->state,
            'country' => $admission->country,
            'pincode' => $admission->pincode,
            'attracted' => $admission->attracted,
            'strengths' => $admission->strengths,
            'remarks' => $admission->remarks,
            'comments' => $admission->comments,
            'printed' => $admission->printed,
            'created_at' => $admission->created_at,
            'updated_at' => $admission->updated_at,
        ];

        return response()->json([
            'code' => 200,
            'message' => 'Student data fetched successfully.',
            'data' => $data
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'Failed to fetch student data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function importAdmissions()
{
    try {
        $filePath = storage_path('app/public/imports/new_admission_0310.csv');

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'CSV file not found.'], 404);
        }

        $imported = 0;
        $errors = [];

        $csv = [];
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 0, ',', '"'); // for properly quoted fields

        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            $csv[] = $data;
        }
        fclose($handle);

        foreach ($csv as $index => $row) {
            if (count($row) !== count($headers)) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => "Header and row column count mismatch. Expected " . count($headers) . " but got " . count($row)
                ];
                continue;
            }

            try {
                $rowData = array_combine($headers, $row);

                // Parse nested JSONs
                $father = json_decode($rowData['father_details'] ?? '{}', true);
                $mother = json_decode($rowData['mother_details'] ?? '{}', true);
                $address = json_decode($rowData['address'] ?? '{}', true);
                $siblings_raw = json_decode($rowData['siblings'] ?? '{}', true);
                $other_info = json_decode($rowData['other_info'] ?? '{}', true);

                // Build siblings array from parallel arrays
                $siblings = [];
                if (isset($siblings_raw['name']) && is_array($siblings_raw['name'])) {
                    foreach ($siblings_raw['name'] as $i => $name) {
                        if (trim($name) !== '' && strtolower($name) !== 'no siblings in this school') {
                            $siblings[] = [
                                'name' => $name ?? '',
                                'class' => $siblings_raw['class'][$i] ?? '',
                                'roll_no' => $siblings_raw['roll_no'][$i] ?? ''
                            ];
                        }
                    }
                }

                // Build the final JSON data
                $jsonData = [
                    'first_name' => $rowData['name'] ?? '',
                    'last_name' => $rowData['last_name'] ?? '',
                    'ay_id' => (int) $rowData['ay_id'],
                    'class' => $rowData['class'] ?? '',
                    'gender' => strtolower($rowData['gender'] ?? '') === 'female' ? 'f' : 'm',
                    'dob' => $rowData['date_of_birth'] ?? '',
                    'aadhaar' => preg_replace('/[^0-9]/', '', $rowData['aadhaar_no'] ?? ''),

                    'city' => $address['city'] ?? '',
                    'state' => $address['state'] ?? '',
                    'country' => $address['country'] ?? 'India',
                    'pincode' => $address['pincode'] ?? '',
                    'address_1' => $address['address_1'] ?? '',
                    'address_2' => $address['address_2'] ?? '',

                    'last_school' => 'NA',
                    'last_school_address' => 'NA',

                    // Father
                    'father_name' => $father['first_name'] ?? '',
                    'father_surname' => $father['last_name'] ?? '',
                    'father_occupation' => strtolower($father['occupation'] ?? 'no-occupation'),
                    'father_mobile' => $father['mobile'] ?? '',
                    'father_email' => $father['email'] ?? '',
                    'father_monthly_income' => $father['monthly_income'] ?? '',

                    // Mother
                    'mother_first_name' => $mother['first_name'] ?? '',
                    'mother_last_name' => $mother['last_name'] ?? '',
                    'mother_occupation' => strtolower($mother['occupation'] ?? 'not-applicable'),
                    'mother_mobile' => $mother['mobile'] ?? '',
                    'mother_email' => $mother['email'] ?? '',
                    'mother_monthly_income' => $mother['monthly_income'] ?? '',

                    // Others
                    'siblings' => $siblings,
                    'attracted' => $other_info['attracted'] ?? '',
                    'strengths' => $other_info['strengths'] ?? '',
                    'remarks' => $other_info['remarks'] ?? '',
                    'interview_status' => $rowData['interview_status'] ?? '0',
                    'added_to_school' => $rowData['added_to_school'] ?? '0',
                    'comments' => $rowData['comments'] ?? '',
                    'printed' => $rowData['printed'] ?? '0',
                ];

                // Handle father occupation details
                if ($jsonData['father_occupation'] === 'business') {
                    $jsonData['father_business_name'] = $father['business'] ?? '';
                    $jsonData['father_business_nature'] = $father['nature'] ?? '';
                    $jsonData['father_work_business_address'] = $father['address_1'] ?? '';
                    $jsonData['father_business_city'] = $address['city'] ?? '';
                    $jsonData['father_business_state'] = $address['state'] ?? '';
                    $jsonData['father_business_country'] = $address['country'] ?? '';
                    $jsonData['father_business_pincode'] = $address['pincode'] ?? '';
                } elseif ($jsonData['father_occupation'] === 'employed') {
                    $jsonData['father_employer_name'] = $father['employer'] ?? '';
                    $jsonData['father_designation'] = $father['designation'] ?? '';
                    $jsonData['father_work_business_address'] = $father['address_1'] ?? '';
                    $jsonData['father_work_city'] = $address['city'] ?? '';
                    $jsonData['father_work_state'] = $address['state'] ?? '';
                    $jsonData['father_work_country'] = $address['country'] ?? '';
                    $jsonData['father_work_pincode'] = $address['pincode'] ?? '';
                }

                // Handle mother occupation details
                if ($jsonData['mother_occupation'] === 'business') {
                    $jsonData['mother_business_name'] = $mother['business'] ?? '';
                    $jsonData['mother_business_nature'] = $mother['nature'] ?? '';
                    $jsonData['mother_work_business_address'] = $mother['address_1'] ?? '';
                    $jsonData['mother_business_city'] = $address['city'] ?? '';
                    $jsonData['mother_business_state'] = $address['state'] ?? '';
                    $jsonData['mother_business_country'] = $address['country'] ?? '';
                    $jsonData['mother_business_pincode'] = $address['pincode'] ?? '';
                } elseif ($jsonData['mother_occupation'] === 'employed') {
                    $jsonData['mother_employer_name'] = $mother['employer'] ?? '';
                    $jsonData['mother_designation'] = $mother['designation'] ?? '';
                    $jsonData['mother_work_business_address'] = $mother['address_1'] ?? '';
                    $jsonData['mother_work_city'] = $address['city'] ?? '';
                    $jsonData['mother_work_state'] = $address['state'] ?? '';
                    $jsonData['mother_work_country'] = $address['country'] ?? '';
                    $jsonData['mother_work_pincode'] = $address['pincode'] ?? '';
                }

                // Send internal request
                $fakeRequest = new \Illuminate\Http\Request(['json_data' => json_encode($jsonData)]);
                $response = $this->registerAdmission($fakeRequest);

                $decoded = json_decode($response->getContent(), true);
                if ($response->getStatusCode() === 200) {
                    $imported++;
                } else {
                    $msg = $decoded['message'] ?? 'Unknown error';
                    if (isset($decoded['error'])) {
                        $msg .= ' | Error: ' . $decoded['error'];
                    }
                    if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                        foreach ($decoded['errors'] as $field => $err) {
                            $msg .= " | $field: " . (is_array($err) ? implode(', ', $err) : $err);
                        }
                    }
                    $errors[] = ['row' => $index + 2, 'message' => $msg];
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => 'Exception: ' . $e->getMessage() . ' (Line ' . $e->getLine() . ')'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Imported $imported records.",
            'errors' => $errors
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Import process failed.',
            'error' => $e->getMessage()
        ]);
    }
}
}