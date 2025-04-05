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
                'class' => 'required|string|max:10',
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
                'interview_status' => 'required|in:0,1',
                'added_to_school' => 'required|in:0,1',
                'comments' => 'nullable|string',
                'printed' => 'required|in:0,1',
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
                'error' => $e->getMessage(),
            ], 500);
        }
    }private function handleOccupationValidation($validated, $jsonData)
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
                'file_url' => Storage::url($filePath),
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
        // Default values for pagination
        $limit = $request->input('limit', 10);  // Default to 10 if not provided
        $offset = $request->input('offset', 0); // Default to 0 if not provided

        // Filters (if provided)
        $search = $request->input('search', ''); // Search for name
        $class = $request->input('class', ''); // Filter by class
        $year = $request->input('year', ''); // Filter by academic year (ay_id)
        $adPaid = $request->input('ad_paid', ''); // Filter by ad_paid (payment status)
        $interviewStatus = $request->input('interview_status', ''); // Filter by interview status
        $addedToSchool = $request->input('added_to_school', ''); // Filter by added_to_school status

        // Query to get the new admissions
        $query = NewAdmissionModel::query();

        // Apply search filter for name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Apply class filter
        if ($class) {
            $query->where('class', 'like', "%{$class}%");
        }

        // Apply academic year filter
        if ($year) {
            $query->where('ay_id', '=', $year);
        }

        // Apply ad_paid filter (0 for unpaid, 1 for paid)
        if ($adPaid !== '') {
            $query->where('ad_paid', '=', $adPaid);
        }

        // Apply interview status filter (0 for not cleared, 1 for cleared)
        if ($interviewStatus !== '') {
            $query->where('interview_status', '=', $interviewStatus);
        }

        // Apply added_to_school filter (0 for not added, 1 for added)
        if ($addedToSchool !== '') {
            $query->where('added_to_school', '=', $addedToSchool);
        }

        // Get the total number of entries
        $totalEntries = $query->count();

        // Get the paginated records
        $admissions = $query->offset($offset)
                            ->limit($limit)
                            ->get();

        // Prepare the data by adding the photo URLs
        $admissionsData = $admissions->map(function ($admission) {
            // Get the URLs of the photos from the UploadModel using file IDs
            $childPhotoUrl = $admission->child_photo_id 
                ? UploadModel::find($admission->child_photo_id)->file_url
                : null;
            $fatherPhotoUrl = $admission->father_photo_id
                ? UploadModel::find($admission->father_photo_id)->file_url
                : null;
            $motherPhotoUrl = $admission->mother_photo_id
                ? UploadModel::find($admission->mother_photo_id)->file_url
                : null;

            return [
                'sn' => $admission->id,
                'application_no' => $admission->application_no,
                'name' => $admission->first_name . ' ' . $admission->last_name,
                'gender' => $admission->gender,
                'dob' => $admission->date_of_birth,
                'class' => $admission->class,
                'ad_paid' => $admission->ad_paid,
                'interview_status' => $admission->interview_status,
                'added_to_school' => $admission->added_to_school,
                'child_photo_url' => $childPhotoUrl,
                'father_photo_url' => $fatherPhotoUrl,
                'mother_photo_url' => $motherPhotoUrl,
            ];
        });

        // Return the response
        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'New admissions retrieved successfully.',
            'data' => $admissionsData,
            'total_entries' => $totalEntries,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'success' => false,
            'message' => 'Failed to retrieve data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function getStudentData($id)
{
    try {
        // Fetch the student data from the 't_new_admission' table
        $student = NewAdmissionModel::find($id);

        if (!$student) {
            return response()->json([
                'code' => 404,
                'message' => 'Student not found',
            ], 404);
        }

        // Retrieve the uploaded files based on the file IDs from the 'uploads' table
        $fileUrls = [
            'child_photo_url' => $student->child_photo_id ? Storage::url(UploadModel::find($student->child_photo_id)->file_url) : null,
            'father_photo_url' => $student->father_photo_id ? Storage::url(UploadModel::find($student->father_photo_id)->file_url) : null,
            'mother_photo_url' => $student->mother_photo_id ? Storage::url(UploadModel::find($student->mother_photo_id)->file_url) : null,
            'birth_certificate_url' => $student->birth_certificate_id ? Storage::url(UploadModel::find($student->birth_certificate_id)->file_url) : null,
        ];

        // Prepare the data to return
        $studentData = [
            'sn' => $id,  // Serial number is the ID of the student
            'application_no' => $student->application_no,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'gender' => $student->gender,
            'dob' => $student->date_of_birth,
            'class' => $student->class,
            'aadhaar_no' => $student->aadhaar_no,
            'father_name' => $student->father_name,
            'father_surname' => $student->father_surname,
            'father_occupation' => $student->father_occupation,
            'father_mobile' => $student->father_mobile,
            'father_email' => $student->father_email,
            'mother_first_name' => $student->mother_first_name,
            'mother_last_name' => $student->mother_last_name,
            'mother_name' => $student->mother_name,
            'mother_occupation' => $student->mother_occupation,
            'mother_mobile' => $student->mother_mobile,
            'mother_email' => $student->mother_email,
            'siblings' => json_decode($student->siblings),
            'address_1' => $student->address_1,
            'address_2' => $student->address_2,
            'city' => $student->city,
            'state' => $student->state,
            'country' => $student->country,
            'pincode' => $student->pincode,
            'attracted' => $student->attracted,
            'strengths' => $student->strengths,
            'remarks' => $student->remarks,
            'interview_status' => $student->interview_status,
            'added_to_school' => $student->added_to_school,
            'comments' => $student->comments,
            'printed' => $student->printed,
            'file_links' => $fileUrls,  // Include file URLs
        ];

        return response()->json([
            'code' => 200,
            'message' => 'Student data fetched successfully',
            'data' => $studentData,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'Failed to fetch student data',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}