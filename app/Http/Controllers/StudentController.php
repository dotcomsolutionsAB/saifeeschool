<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // For validations
use Illuminate\Support\Facades\DB; // For database operations
use Illuminate\Support\Facades\Hash; // For password hashing
use Illuminate\Support\Facades\Storage; // For file storage
use Illuminate\Support\Facades\File; // File handling
use Illuminate\Support\Str; // For string manipulations
use App\Models\StudentModel; // Student model
use App\Models\StudentDetailsModel; // Student Details model
use App\Models\AcademicYearModel; // Academic Year model
use App\Models\ClassGroupModel; // Class Group model
use App\Models\StudentClassModel; // Student Class model
use App\Models\User; // User model
use App\Models\FeeModel; // Fee model
use App\Models\UploadModel; // File uploads model
use League\Csv\Reader; // For CSV handling
use League\Csv\Statement; // For CSV processing
use Mpdf\Mpdf; // For PDF generation
use App\Http\Controllers\RazorpayController; // Razorpay Controller
use App\Http\Controllers\RazorpayService; // Razorpay Service
use Illuminate\Validation\Rule; // For advanced validation rules
use Carbon\Carbon; // For date manipulation

class StudentController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->json()->all();

        // Base validation for general fields
        $validated = Validator::make($data, [
            // Student fields
            'st_roll_no' => 'required|string|max:255|unique:t_students,st_roll_no',
            'st_first_name' => 'required|string|max:255',
            'st_last_name' => 'required|string|max:255',
            'st_gender' => 'required|in:M,F',
            'st_dob' => 'required|date',
            'st_blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'st_bohra' => 'required|in:0,1',
            'st_its_id' => 'required|string|max:255|unique:t_students,st_its_id',
            'st_house' => 'required|in:red,blue,green,gold',
            'st_wallet' => 'required|numeric',
            'st_deposit' => 'required|numeric',
            'st_gmail_address' => 'nullable|string',
            'st_mobile' => 'nullable|string|max:20',
            'st_external' => 'required|in:0,1',
            'st_on_roll' => 'required|in:0,1',
            'st_year_of_admission' => 'required|string|max:255',
            'st_admitted' => 'required|string|max:255',
            'st_admitted_class' => 'required|string|max:255',
            'st_flag' => 'required|string|max:255',
            // Student Details fields
            'aadhaar_no' => 'required|digits:12|unique:t_student_details,aadhaar_no',
            'residential_address1' => 'required|string|max:1000',
            'residential_address2' => 'nullable|string|max:1000',
            'residential_address3' => 'nullable|string|max:1000',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'pincode' => 'required|digits:6',
            'class_group' => 'required|integer|min:1',
            // Parent details
            'f_name' => 'required|string|max:255',
            'f_email' => 'required|email|max:255',
            'f_contact' => 'required|string|max:20',
            'm_name' => 'required|string|max:255',
            'm_email' => 'required|email|max:255',
            'm_contact' => 'required|string|max:20',
            'f_occupation' => 'required|in:employed,self-employed,none',
            'm_occupation' => 'required|in:employed,self-employed,home-maker',
        ])->validate();

        // Additional validation based on father's occupation
        if ($validated['f_occupation'] === 'self-employed') {
            $validated = array_merge($validated, Validator::make($data, [
                'f_business_name' => 'required|string|max:255',
                'f_business_nature' => 'required|string|max:255',
                'f_business_address1' => 'required|string|max:255',
                'f_business_city' => 'required|string|max:255',
                'f_business_state' => 'required|string|max:255',
                'f_business_country' => 'required|string|max:255',
                'f_business_pincode' => 'required|string|max:10',
            ])->validate());
        } elseif ($validated['f_occupation'] === 'employed') {
            $validated = array_merge($validated, Validator::make($data, [
                'f_employer_name' => 'required|string|max:255',
                'f_designation' => 'required|string|max:255',
                'f_work_address1' => 'required|string|max:255',
                'f_work_city' => 'required|string|max:255',
                'f_work_state' => 'required|string|max:255',
                'f_work_country' => 'required|string|max:255',
                'f_work_pincode' => 'required|string|max:10',
            ])->validate());
        }

        // Additional validation based on mother's occupation
        if ($validated['m_occupation'] === 'self-employed') {
            $validated = array_merge($validated, Validator::make($data, [
                'm_business_name' => 'required|string|max:255',
                'm_business_nature' => 'required|string|max:255',
                'm_business_address1' => 'required|string|max:255',
                'm_business_city' => 'required|string|max:255',
                'm_business_state' => 'required|string|max:255',
                'm_business_country' => 'required|string|max:255',
                'm_business_pincode' => 'required|string|max:10',
            ])->validate());
        } elseif ($validated['m_occupation'] === 'employed') {
            $validated = array_merge($validated, Validator::make($data, [
                'm_employer_name' => 'required|string|max:255',
                'm_designation' => 'required|string|max:255',
                'm_work_address1' => 'required|string|max:255',
                'm_work_city' => 'required|string|max:255',
                'm_work_state' => 'required|string|max:255',
                'm_work_country' => 'required|string|max:255',
                'm_work_pincode' => 'required|string|max:10',
            ])->validate());
        }

        try {
            // Generate user_token
            $userToken = md5($validated['st_roll_no'] . Str::random(10) . time());

            // Create student record
            $student = StudentModel::create([
                'st_roll_no' => $validated['st_roll_no'],
                'st_first_name' => $validated['st_first_name'],
                'st_last_name' => $validated['st_last_name'],
                'st_gender' => $validated['st_gender'],
                'st_dob' => $validated['st_dob'],
                'st_blood_group' => $validated['st_blood_group'],
                'st_bohra' => $validated['st_bohra'],
                'st_its_id' => $validated['st_its_id'],
                'st_house' => $validated['st_house'],
                'st_wallet' => $validated['st_wallet'],
                'st_deposit' => $validated['st_deposit'],
                'st_gmail_address' => $validated['st_gmail_address'],
                'st_mobile' => $validated['st_mobile'],
                'st_external' => $validated['st_external'],
                'st_on_roll' => $validated['st_on_roll'],
                'st_year_of_admission' => $validated['st_year_of_admission'],
                'st_admitted' => $validated['st_admitted'],
                'st_admitted_class' => $validated['st_admitted_class'],
                'st_flag' => $validated['st_flag'],
                'user_token' => $userToken,
            ]);

            // Create student details record
            StudentDetailsModel::create([
                'st_id' => $student->id,
                'aadhaar_no' => $validated['aadhaar_no'],
                'residential_address1' => $validated['residential_address1'],
                'residential_address2' => $validated['residential_address2'],
                'residential_address3' => $validated['residential_address3'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'country' => $validated['country'],
                'pincode' => $validated['pincode'],
                'class_group' => $validated['class_group'],
                'f_name' => $validated['f_name'],
                'f_email' => $validated['f_email'],
                'f_contact' => $validated['f_contact'],
                'f_occupation' => $validated['f_occupation'],
                'f_business_name' => $validated['f_business_name'] ?? null,
                'f_business_nature' => $validated['f_business_nature'] ?? null,
                'f_business_address1' => $validated['f_business_address1'] ?? null,
                'f_business_city' => $validated['f_business_city'] ?? null,
                'f_business_state' => $validated['f_business_state'] ?? null,
                'f_business_country' => $validated['f_business_country'] ?? null,
                'f_business_pincode' => $validated['f_business_pincode'] ?? null,
                'f_employer_name' => $validated['f_employer_name'] ?? null,
                'f_designation' => $validated['f_designation'] ?? null,
                'f_work_address1' => $validated['f_work_address1'] ?? null,
                'f_work_city' => $validated['f_work_city'] ?? null,
                'f_work_state' => $validated['f_work_state'] ?? null,
                'f_work_country' => $validated['f_work_country'] ?? null,
                'f_work_pincode' => $validated['f_work_pincode'] ?? null,
                'm_name' => $validated['m_name'],
                'm_email' => $validated['m_email'],
                'm_contact' => $validated['m_contact'],
                'm_occupation' => $validated['m_occupation'],
                'm_business_name' => $validated['m_business_name'] ?? null,
                'm_business_nature' => $validated['m_business_nature'] ?? null,
                'm_business_address1' => $validated['m_business_address1'] ?? null,
                'm_business_city' => $validated['m_business_city'] ?? null,
                'm_business_state' => $validated['m_business_state'] ?? null,
                'm_business_country' => $validated['m_business_country'] ?? null,
                'm_business_pincode' => $validated['m_business_pincode'] ?? null,
                'm_employer_name' => $validated['m_employer_name'] ?? null,
                'm_designation' => $validated['m_designation'] ?? null,
                'm_work_address1' => $validated['m_work_address1'] ?? null,
                'm_work_city' => $validated['m_work_city'] ?? null,
                'm_work_state' => $validated['m_work_state'] ?? null,
                'm_work_country' => $validated['m_work_country'] ?? null,
                'm_work_pincode' => $validated['m_work_pincode'] ?? null,
            ]);

            // Create user record
            User::create([
                'name' => $validated['st_first_name'] . ' ' . $validated['st_last_name'],
                'email' => $validated['st_gmail_address'],
                'password' => bcrypt($validated['st_roll_no']),
                'role' => 'student',
                'username' => $validated['st_gmail_address'],
                'user_token' => $userToken,
            ]);

            return response()->json([
                'message' => 'Student registered successfully',
                'data' => $student->toArray(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadFiles(Request $request)
    {
        $request->validate([
            'st_roll_no' => 'required|exists:t_students,st_roll_no',
            'file_type' => 'required|array|min:1',
            'file_type.*' => 'required|in:photo,birth_certificate,aadhar,attachment',
            'file' => 'required|array|min:1',
            'file.*' => 'required|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $studentRollNo = $request->input('st_roll_no');
        $fileTypes = $request->input('file_type');
        $files = $request->file('file');

        try {
            // Fetch the student using roll number
            $student = StudentModel::where('st_roll_no', $studentRollNo)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.',
                ], 404);
            }

            // Check if the student has a matching user by email
            $user = User::where('email', $student->st_gmail_address)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching user found for the student email.',
                ], 404);
            }

            $userToken = $student->user_token;
            $directory = "uploads/students/{$userToken}";

            // Ensure directory exists
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            $uploadedFiles = [];

            foreach ($fileTypes as $index => $fileType) {
                if (!isset($files[$index])) {
                    continue;
                }

                $file = $files[$index];
                $fileName = match ($fileType) {
                    'photo' => "{$studentRollNo}_photo.{$file->getClientOriginalExtension()}",
                    'birth_certificate' => "{$studentRollNo}_birth.{$file->getClientOriginalExtension()}",
                    'aadhar' => "{$studentRollNo}_aadhar.{$file->getClientOriginalExtension()}",
                    'attachment' => "{$studentRollNo}_attachment.{$file->getClientOriginalExtension()}",
                };

                $filePath = $file->storeAs($directory, $fileName, 'public');

                // Save file details in the uploads table (replace if exists)
                $upload = UploadModel::updateOrCreate(
                    ['file_name' => $fileName],
                    [
                        'file_ext' => $file->getClientOriginalExtension(),
                        'file_url' => $filePath,
                        'file_size' => $file->getSize(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $uploadedFiles[] = [
                    'file_id' => $upload->id,
                    'file_name' => $upload->file_name,
                    'file_url' => Storage::url($filePath),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully.',
                'data' => $uploadedFiles,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // update
    public function update(Request $request, $id)
    {
        // Find the student record
        $student = StudentModel::findOrFail($id);
        $studentDetails = StudentDetailsModel::where('st_id', $id)->firstOrFail();

        // Validate the request data
        $validated = $request->validate([
            'st_roll_no' =>[
                'required',
                'string',
                'max:255',
                Rule::unique('t_students', 'st_roll_no')->ignore($id),
            ],
            'st_first_name' => 'required|string|max:255',
            'st_last_name' => 'required|string|max:255',
            'st_gender' => 'required|in:M,F',
            'st_dob' => 'required|date|before:today',
            'st_blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'st_bohra' => 'required|in:0,1',
            'st_its_id' => 'required|string|max:255|unique:t_students,st_its_id,' . $student->id,
            'st_house' => 'required|in:red,blue,green,gold',
            'st_wallet' => 'required|numeric|min:0',
            'st_deposit' => 'required|numeric|min:0',
            'st_gmail_address' => 'nullable|email|max:255',
            'st_mobile' => 'nullable|string|max:20',
            'st_external' => 'required|in:0,1',
            'st_on_roll' => 'required|in:0,1',
            'st_year_of_admission' => 'required|string|max:255',
            'st_admitted' => 'required|string|max:255',
            'st_admitted_class' => 'required|string|max:255',
            'st_flag' => 'required|string|max:255',
            // Attachments validation
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'aadhaar_card' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'photo_pic' => 'nullable|file|mimes:jpeg,jpg,png|max:2048',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            // 'aadhaar_no' => 'nullable|digits:12|unique:t_student_details,aadhaar_no,' . $studentDetails->id,
            'aadhaar_no' => [
                'nullable',
                'digits:12',
                Rule::unique('t_student_details', 'aadhaar_no')
                    ->where(function ($query) use ($student) {
                        return $query->where('st_id', '!=', $student->id);
                    }),
            ],
            'residential_address1' => 'required|string|max:255',
            'residential_address2' => 'nullable|string|max:255',
            'residential_address3' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'pincode' => 'required|digits:6',
            'class_group' => 'required|integer',
            'f_name' => 'required|string|max:255',
            'f_email' => 'nullable|email|max:255',
            'f_contact' => 'nullable|string|max:20',
            'f_occupation' => 'required|in:employed,self-employed,none',
            'm_name' => 'required|string|max:255',
            'm_contact' => 'nullable|string|max:20',
            'm_occupation' => 'required|in:employed,self-employed,home-maker',
        ]);

        // Handle father occupation
        if ($validated['f_occupation'] === 'self-employed') {
            $validated = array_merge($validated, $request->validate([
                'f_business_name' => 'required|string|max:255',
                'f_business_nature' => 'required|string|max:255',
                'f_business_address1' => 'required|string|max:255',
                'f_business_city' => 'required|string|max:255',
                'f_business_state' => 'required|string|max:255',
                'f_business_country' => 'required|string|max:255',
                'f_business_pincode' => 'required|digits:6',
            ]));
            $validated = array_merge($validated, [
                'f_employer_name' => null,
                'f_designation' => null,
                'f_work_address1' => null,
                'f_work_city' => null,
            ]);
        } elseif ($validated['f_occupation'] === 'employed') {
            $validated = array_merge($validated, $request->validate([
                'f_employer_name' => 'required|string|max:255',
                'f_designation' => 'required|string|max:255',
                'f_work_address1' => 'required|string|max:255',
                'f_work_city' => 'required|string|max:255',
                'f_work_state' => 'required|string|max:255',
                'f_work_country' => 'required|string|max:255',
                'f_work_pincode' => 'required|digits:6',
            ]));
            $validated = array_merge($validated, [
                'f_business_name' => null,
                'f_business_nature' => null,
                'f_business_address1' => null,
            ]);
        }

        // Handle mother occupation
        if ($validated['m_occupation'] === 'self-employed') {
            $validated = array_merge($validated, $request->validate([
                'm_business_name' => 'required|string|max:255',
                'm_business_nature' => 'required|string|max:255',
                'm_business_address1' => 'required|string|max:255',
                'm_business_city' => 'required|string|max:255',
                'm_business_state' => 'required|string|max:255',
                'm_business_country' => 'required|string|max:255',
                'm_business_pincode' => 'required|digits:6',
            ]));
            $validated = array_merge($validated, [
                'm_employer_name' => null,
                'm_designation' => null,
                'm_work_address1' => null,
                'm_work_city' => null,
            ]);
        } elseif ($validated['m_occupation'] === 'employed') {
            $validated = array_merge($validated, $request->validate([
                'm_employer_name' => 'required|string|max:255',
                'm_designation' => 'required|string|max:255',
                'm_work_address1' => 'required|string|max:255',
                'm_work_city' => 'required|string|max:255',
                'm_work_state' => 'required|string|max:255',
                'm_work_country' => 'required|string|max:255',
                'm_work_pincode' => 'required|digits:6',
            ]));
            $validated = array_merge($validated, [
                'm_business_name' => null,
                'm_business_nature' => null,
                'm_business_address1' => null,
            ]);
        }

        try {

            // Handle file uploads
            $photoId = null;
            $birthCertificateId = null;
            $aadhaarId = null;
            $attachmentId = null;

            // if ($request->hasFile('photo_pic')) {
            //     $photoFile = $request->file('photo_pic');
            //     $photoPath = $photoFile->store('uploads/students/student_profile_images', 'public');
            //     $photoId = UploadModel::create([
            //         'file_name' => pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME),
            //         'file_ext' => $photoFile->getClientOriginalExtension(),
            //         'file_url' => $photoPath,
            //         'file_size' => $photoFile->getSize(),
            //     ])->id;
            // }

            if ($request->hasFile('photo_pic')) {
                // Get the existing photo file details
                $existingPhoto = UploadModel::find($student->photo_id);
            
                // Upload the new file
                $photoFile = $request->file('photo_pic');
                $photoPath = $photoFile->store('uploads/students/student_profile_images', 'public');

                // Generate the full file URL
                $fullFilePhotoUrl = url('storage/' . $photoPath);

                $photoId = UploadModel::create([
                    'file_name' => pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $photoFile->getClientOriginalExtension(),
                    'file_url' => $fullFilePhotoUrl,
                    'file_size' => $photoFile->getSize(),
                ])->id;
            
                // Delete the old file if it exists
                if ($existingPhoto) {
                    $oldFilePath = public_path('storage/' . $existingPhoto->file_url);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath); // Remove file from server
                    }
                    $existingPhoto->delete(); // Remove record from the database
                }
            } else {
                $photoId = $student->photo_id; // Keep the old photo if no new file is uploaded
            }


            // if ($request->hasFile('birth_certificate')) {
            //     $birthCertificateFile = $request->file('birth_certificate');
            //     $birthCertificatePath = $birthCertificateFile->store('uploads/students/birth_certificates', 'public');
            //     $birthCertificateId = UploadModel::create([
            //         'file_name' => pathinfo($birthCertificateFile->getClientOriginalName(), PATHINFO_FILENAME),
            //         'file_ext' => $birthCertificateFile->getClientOriginalExtension(),
            //         'file_url' => $birthCertificatePath,
            //         'file_size' => $birthCertificateFile->getSize(),
            //     ])->id;
            // }

            if ($request->hasFile('birth_certificate')) {
                // Retrieve the existing birth certificate record
                $existingBirthCertificate = UploadModel::find($student->birth_certificate_id);
            
                // Upload the new file
                $birthCertificateFile = $request->file('birth_certificate');
                $birthCertificatePath = $birthCertificateFile->store('uploads/students/birth_certificates', 'public');

                // Generate the full file URL
                $fullFileBirthCertificateUrl = url('storage/' . $birthCertificatePath);

                $birthCertificateId = UploadModel::create([
                    'file_name' => pathinfo($birthCertificateFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $birthCertificateFile->getClientOriginalExtension(),
                    'file_url' => $fullFileBirthCertificateUrl,
                    'file_size' => $birthCertificateFile->getSize(),
                ])->id;
            
                // Delete the old file and record if it exists
                if ($existingBirthCertificate) {
                    $oldFilePath = public_path('storage/' . $existingBirthCertificate->file_url);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath); // Remove old file from server
                    }
                    $existingBirthCertificate->delete(); // Remove old record from uploads table
                }
            } else {
                $birthCertificateId = $student->birth_certificate_id; // Keep the existing birth certificate if no new file is uploaded
            }
            

            // if ($request->hasFile('aadhaar_card')) {
            //     $aadhaarFile = $request->file('aadhaar_card');
            //     $aadhaarPath = $aadhaarFile->store('uploads/students/aadhaar_certificate', 'public');
            //     $aadhaarId = UploadModel::create([
            //         'file_name' => pathinfo($aadhaarFile->getClientOriginalName(), PATHINFO_FILENAME),
            //         'file_ext' => $aadhaarFile->getClientOriginalExtension(),
            //         'file_url' => $aadhaarPath,
            //         'file_size' => $aadhaarFile->getSize(),
            //     ])->id;
            // }

            if ($request->hasFile('aadhaar_card')) {
                $existingAadhaar = UploadModel::find($student->aadhaar_id);
            
                $aadhaarFile = $request->file('aadhaar_card');
                $aadhaarPath = $aadhaarFile->store('uploads/students/aadhaar_certificate', 'public');

                // Generate the full file URL
                $fullFileAadhaarUrl = url('storage/' . $aadhaarPath);

                $aadhaarId = UploadModel::create([
                    'file_name' => pathinfo($aadhaarFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $aadhaarFile->getClientOriginalExtension(),
                    'file_url' => $fullFileAadhaarUrl,
                    'file_size' => $aadhaarFile->getSize(),
                ])->id;
            
                if ($existingAadhaar) {
                    $oldFilePath = public_path('storage/' . $existingAadhaar->file_url);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                    $existingAadhaar->delete();
                }
            } else {
                $aadhaarId = $student->aadhaar_id;
            }            

            // if ($request->hasFile('attachment')) {
            //     $attachmentFile = $request->file('attachment');
            //     $attachmentPath = $attachmentFile->store('uploads/students/attachment', 'public');
            //     $attachmentId = UploadModel::create([
            //         'file_name' => pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME),
            //         'file_ext' => $attachmentFile->getClientOriginalExtension(),
            //         'file_url' => $attachmentPath,
            //         'file_size' => $attachmentFile->getSize(),
            //     ])->id;
            // }

            if ($request->hasFile('attachment')) {
                // Retrieve the existing attachment record
                $existingAttachment = UploadModel::find($student->attachment_id);
            
                // Upload the new file
                $attachmentFile = $request->file('attachment');
                $attachmentPath = $attachmentFile->store('uploads/students/attachment', 'public');

                // Generate the full file URL
                $fullFileAttachmentUrl = url('storage/' . $attachmentPath);

                $attachmentId = UploadModel::create([
                    'file_name' => pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $attachmentFile->getClientOriginalExtension(),
                    'file_url' => $fullFileAttachmentUrl,
                    'file_size' => $attachmentFile->getSize(),
                ])->id;
            
                // Delete the old file and record if it exists
                if ($existingAttachment) {
                    $oldFilePath = public_path('storage/' . $existingAttachment->file_url);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath); // Remove old file from server
                    }
                    $existingAttachment->delete(); // Remove old record from uploads table
                }
            } else {
                $attachmentId = $student->attachment_id; // Keep the existing attachment if no new file is uploaded
            }            

            // Update student data
            $student->update([
                'st_roll_no' => $validated['st_roll_no'],
                'st_first_name' => $validated['st_first_name'],
                'st_last_name' => $validated['st_last_name'],
                'st_gender' => $validated['st_gender'],
                'st_dob' => $validated['st_dob'],
                'st_blood_group' => $validated['st_blood_group'],
                'st_bohra' => $validated['st_bohra'],
                'st_its_id' => $validated['st_its_id'],
                'st_house' => $validated['st_house'],
                'st_wallet' => $validated['st_wallet'],
                'st_deposit' => $validated['st_deposit'],
                'st_gmail_address' => $validated['st_gmail_address'],
                'st_mobile' => $validated['st_mobile'],
                'st_external' => $validated['st_external'],
                'st_on_roll' => $validated['st_on_roll'],
                'st_year_of_admission' => $validated['st_year_of_admission'],
                'st_admitted' => $validated['st_admitted'],
                'st_admitted_class' => $validated['st_admitted_class'],
                'st_flag' => $validated['st_flag'],
                'photo_id' => $photoId, // Reference the uploaded profile picture
                'birth_certificate_id' => $birthCertificateId, // Reference the uploaded Birth Certificate
                'aadhaar_id' => $aadhaarId, // Reference the uploaded Aadhaar card
                'attachment_id' => $attachmentId, // Reference the uploaded Attachments
            ]);

            // Update student details
            $studentDetails->update([
                'aadhaar_no' => $validated['aadhaar_no'] ?? null,
                'residential_address1' => $validated['residential_address1'] ?? null,
                'residential_address2' => $validated['residential_address2'] ?? null,
                'residential_address3' => $validated['residential_address3'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'country' => $validated['country'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'class_group' => $validated['class_group'] ?? null,
                'f_name' => $validated['f_name'] ?? null,
                'f_email' => $validated['f_email'] ?? null,
                'f_contact' => $validated['f_contact'] ?? null,
                'f_occupation' => $validated['f_occupation'] ?? null,
                'm_name' => $validated['m_name'] ?? null,
                'm_contact' => $validated['m_contact'] ?? null,
                'm_occupation' => $validated['m_occupation'] ?? null,
            ]);

            return response()->json(['message' => 'Student updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a student and their details
    public function destroy($id)
    {
        try {
            // Attempt to find the student
            $student = StudentModel::with('details')->find($id);
    
            // Check if the student exists
            if (!$student) {
                return response()->json([
                    'message' => 'Student not present.'
                ], 404);
            }
    
            // Check and delete related details
            if ($student->details) {
                $student->details()->delete();
            }
    
            // Delete the student
            $student->delete();
    
            return response()->json([
                'message' => 'Student and their details deleted successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete student.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function index(Request $request, $id = null)
    // {
    //     try {
    //         // Validate the request for optional filters
    //         $validated = $request->validate([
    //             'ay_id' => 'nullable|integer|exists:t_academic_years,id',
    //             'offset' => 'nullable|integer|min:0',
    //             'limit' => 'nullable|integer|min:1|max:100', // Limit parameter with a max of 100
    //             'search' => 'nullable|string|max:255', // Search for name or ITS ID
    //             'bohra' => 'nullable|in:0,1',
    //             'cg_id' => 'nullable|string',
    //             'gender' => 'nullable|in:M,F',
    //             'dob_from' => 'nullable|date',
    //             'dob_to' => 'nullable|date|after_or_equal:dob_from',
    //             'roll_no' => 'nullable|string|max:255',
    //         ]);

    //         $offset = $validated['offset'] ?? 0;
    //         $limit = $validated['limit'] ?? 10; // Default limit to 10

    //         $query = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup']);

    //         // Determine the academic year
    //         $currentAcademicYear = $validated['ay_id']
    //             ? $query->where('id', $validated['ay_id'])->first()
    //             : $query->where('ay_current', '1')->first() ?? $query->orderBy('id', 'desc')->first();

    //         if (!$currentAcademicYear) {
    //             return response()->json([
    //                 'code' => 200,
    //                 'status' => true,
    //                 'data' => [],
    //                 'message' => 'No academic year records found.'
    //             ], 200);
    //         }

    //         if ($id) {
    //             // Fetch a specific student's class details
    //             $studentClass = $currentAcademicYear->studentClasses()
    //                 ->where('st_id', $id)
    //                 ->with(['student', 'classGroup'])
    //                 ->first();

    //             if (!$studentClass) {
    //                 return response()->json([
    //                     'code' => 200,
    //                     'status' => true,
    //                     'data' => [],
    //                     'message' => 'Student not enrolled in the determined academic year.',
    //                 ], 200);
    //             }

    //             $student = $studentClass->student;
    //             $photo = $student->photo_id
    //                 ? UploadModel::where('id', $student->photo_id)->value('file_url')
    //                 : null;

    //             $studentData = $student->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
    //             $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //             $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //             $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
    //             $studentData['photo'] = $photo;

    //             return response()->json([
    //                 'code' => 200,
    //                 'status' => true,
    //                 'message' => 'Student class name fetched successfully.',
    //                 'data' => $studentData
    //             ]);
    //         } else {
    //             // Fetch all student-class records and apply filters
    //             $studentClasses = $currentAcademicYear->studentClasses()->with(['student', 'classGroup']);

    //             // Apply cg_id filter
    //             if (!empty($validated['cg_id'])) {
    //                 $cgIds = explode(',', $validated['cg_id']); // Convert comma-separated cg_id to an array
    //                 $studentClasses->whereIn('cg_id', $cgIds);
    //             }

    //             // Apply filters
    //             if (!empty($validated['search'])) {
    //                 $studentClasses->whereHas('student', function ($query) use ($validated) {
    //                     $searchTerm = '%' . trim($validated['search']) . '%'; // Trim and wildcard the search term
    //                     $query->whereRaw('LOWER(st_first_name) like ?', [strtolower($searchTerm)])
    //                         ->orWhereRaw('LOWER(st_last_name) like ?', [strtolower($searchTerm)])
    //                         ->orWhereRaw('LOWER(st_its_id) like ?', [strtolower($searchTerm)]);
    //                 });
    //             }

    //             if (isset($validated['bohra'])) {
    //                 $studentClasses->whereHas('student', function ($query) use ($validated) {
    //                     $query->where('st_bohra', $validated['bohra']);
    //                 });
    //             }

    //             if (!empty($validated['class_name'])) {
    //                 $studentClasses->whereHas('classGroup', function ($query) use ($validated) {
    //                     $query->where('cg_name', 'like', '%' . $validated['class_name'] . '%');
    //                 });
    //             }

    //             if (!empty($validated['gender'])) {
    //                 $studentClasses->whereHas('student', function ($query) use ($validated) {
    //                     $query->where('st_gender', $validated['gender']);
    //                 });
    //             }

    //             if (!empty($validated['dob_from']) || !empty($validated['dob_to'])) {
    //                 $studentClasses->whereHas('student', function ($query) use ($validated) {
    //                     if (!empty($validated['dob_from'])) {
    //                         $query->where('st_dob', '>=', $validated['dob_from']);
    //                     }
    //                     if (!empty($validated['dob_to'])) {
    //                         $query->where('st_dob', '<=', $validated['dob_to']);
    //                     }
    //                 });
    //             }

    //             if (!empty($validated['roll_no'])) {
    //                 $studentClasses->whereHas('student', function ($query) use ($validated) {
    //                     $query->where('st_roll_no', $validated['roll_no']);
    //                 });
    //             }

    //             // Get the filtered results
    //             $studentClasses = $studentClasses->get();

    //             if ($studentClasses->isEmpty()) {
    //                 return response()->json([
    //                     'code' => 200,
    //                     'status' => true,
    //                     'data' => [],
    //                     'message' => 'No students match the given criteria.'
    //                 ], 200);
    //             }

    //             $data = $studentClasses->map(function ($studentClass) {
    //                 $student = $studentClass->student;

    //                 if (!$student) {
    //                     return null;
    //                 }

    //                 $photo = $student->photo_id
    //                     ? UploadModel::where('id', $student->photo_id)->value('file_url')
    //                     : null;

    //                 $studentData = $student->makeHidden(['created_at', 'updated_at'])->toArray();
    //                 $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //                 $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //                 $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
    //                 $studentData['photo'] = $photo;

    //                 return $studentData;
    //             })->filter();

    //             return response()->json([
    //                 'code' => 200,
    //                 'status' => true,
    //                 'message' => 'Student class names fetched successfully.',
    //                 'academic_year' => $currentAcademicYear->ay_name,
    //                 'data' => $data->slice($offset, $limit)->values(),
    //                 'count' => $data->count()
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'code' => 500,
    //             'status' => false,
    //             'message' => 'An error occurred while fetching student class names.',
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }

    public function index(Request $request, $id = null)
    {
        try {
            // Validate the request for filters
            $validated = $request->validate([
                'ay_id' => 'nullable|integer|exists:t_academic_years,id',
                'offset' => 'nullable|integer|min:0',
                'limit' => 'nullable|integer|min:1|max:100', // Limit parameter with a max of 100
                'search' => 'nullable|string|max:255', // Search for name or ITS ID
                'bohra' => 'nullable|in:0,1',
                'cg_id' => 'nullable|string',
                'gender' => 'nullable|in:M,F',
            ]);

            // Set defaults for offset and limit
            $offset = $validated['offset'] ?? 0;
            $limit = $validated['limit'] ?? 10;

            // Get the academic year
            $academicYear = AcademicYearModel::query()
                ->where('id', $validated['ay_id'] ?? null)
                ->orWhere('ay_current', 1)
                ->orderBy('id', 'desc')
                ->first();

            if (!$academicYear) {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'data' => [],
                    'message' => 'No academic year records found.',
                ]);
            }

            // If $id is passed, fetch the specific student's details
            if ($id) {
                $studentClass = $academicYear->studentClasses()
                    ->where('st_id', $id)
                    ->with(['student', 'classGroup'])
                    ->first();

                if (!$studentClass) {
                    return response()->json([
                        'code' => 200,
                        'status' => true,
                        'data' => [],
                        'message' => 'Student not found for the given academic year.',
                    ]);
                }

                $student = $studentClass->student;

                $photo = $student->photo_id
                    ? UploadModel::where('id', $student->photo_id)->value('file_url')
                    : null;

                $studentData = [
                    'id' => $student->id,
                    'st_roll_no' => $student->st_roll_no,
                    'st_first_name' => $student->st_first_name,
                    'st_last_name' => $student->st_last_name,
                    'st_gender' => $student->st_gender === 'M' ? 'Male' : 'Female',
                    'st_dob' => $student->st_dob ? \Carbon\Carbon::parse($student->st_dob)->format('d-m-Y') : null,
                    'class_name' => $studentClass->classGroup->cg_name ?? 'Class group not found',
                    'photo' => $photo,
                ];

                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Student fetched successfully.',
                    'academic_year' => $academicYear->ay_name,
                    'data' => $studentData,
                ]);
            }

            // Query the student classes for the selected academic year
            $studentClassesQuery = $academicYear->studentClasses()->with(['student', 'classGroup']);

            // Apply filters
            if (!empty($validated['cg_id'])) {
                $cgIds = explode(',', $validated['cg_id']);
                $studentClassesQuery->whereIn('cg_id', $cgIds);
            }

            if (!empty($validated['search'])) {
                $searchTerm = '%' . trim($validated['search']) . '%';
                $studentClassesQuery->whereHas('student', function ($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(st_first_name) like ?', [strtolower($searchTerm)])
                        ->orWhereRaw('LOWER(st_last_name) like ?', [strtolower($searchTerm)])
                        ->orWhereRaw('LOWER(st_its_id) like ?', [strtolower($searchTerm)]);
                });
            }

            if (isset($validated['bohra'])) {
                $studentClassesQuery->whereHas('student', function ($query) use ($validated) {
                    $query->where('st_bohra', $validated['bohra']);
                });
            }

            if (!empty($validated['gender'])) {
                $studentClassesQuery->whereHas('student', function ($query) use ($validated) {
                    $query->where('st_gender', $validated['gender']);
                });
            }

            // Paginate and fetch results
            $studentClasses = $studentClassesQuery
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Map the response data
            $data = $studentClasses->map(function ($studentClass) {
                $student = $studentClass->student;

                if (!$student) {
                    return null;
                }

                $photo = $student->photo_id
                    ? UploadModel::where('id', $student->photo_id)->value('file_url')
                    : null;

                return [
                    'id' => $student->id,
                    'st_roll_no' => $student->st_roll_no,
                    'st_first_name' => $student->st_first_name,
                    'st_last_name' => $student->st_last_name,
                    'st_gender' => $student->st_gender === 'M' ? 'Male' : 'Female',
                    'st_dob' => $student->st_dob ? \Carbon\Carbon::parse($student->st_dob)->format('d-m-Y') : null,
                    'class_name' => $studentClass->classGroup->cg_name ?? 'Class group not found',
                    'photo' => $photo,
                ];
            })->filter()->values();

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Students fetched successfully.',
                'academic_year' => $academicYear->ay_name,
                'data' => $data,
                'total' => $studentClassesQuery->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching students.',
                'error' => $e->getMessage(),
            ]);
        }
    }



    // csv
    public function importStudentCsv(Request $request)
    {
        // Set the execution time to 2 minutes (120 seconds)
        ini_set('max_execution_time', 120);
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/student.csv');

            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }

            // Truncate the table before import
            StudentClassModel::truncate();

            StudentModel::truncate();

            // Fetch the `ay_id` where `ay_current = 1` from the `academic_years` table
            $currentAcademicYear = AcademicYearModel::where('ay_current', '1')->first();

            if (!$currentAcademicYear) {
                return response()->json([
                    'message' => 'No current academic year found in the database.',
                ], 404);
            }
    
            // Fetch the CSV content using file_get_contents
            $csvContentStudent = file_get_contents($csvFilePath);

            // Parse the CSV content using League\Csv
            $csvStudent = Reader::createFromString($csvContentStudent);

            // Set the header offset (first row as headers)
            $csvStudent->setHeaderOffset(0);

            // Process the CSV records
            $recordsCsv = (new Statement())->process($csvStudent);

            foreach ($recordsCsv as $row) {

            // Define the allowed ENUM values for st_house
            $allowedStHouseValues = ['red', 'blue', 'green', 'gold'];

            // Validate st_house
            if (!in_array($row['st_house'], $allowedStHouseValues)) {
                $row['st_house'] = null; // Set to null if the value is invalid
            }

            // Define the allowed ENUM values for st_gender
            $allowedStGenderValues = ['M', 'F'];

            // Validate st_gender
            if (!in_array($row['st_gender'], $allowedStGenderValues)) {
                $row['st_gender'] = null; // Set to null if the value is invalid
            }

                // Add or update student
                $student = StudentModel::updateOrCreate(
                    ['id' => $row['st_id']], // Match by student ID
                    [
                        'st_roll_no' => $row['st_roll_no'] ?? 'NULL',
                        'st_first_name' => $row['st_first_name'] ?? 'NULL',
                        'st_last_name' => $row['st_last_name'] ?? 'NULL',
                        'st_gender' => $row['st_gender'],
                        'st_dob' => $row['dob'] ?? 'NULL',
                        'st_bohra' => $row['st_bohra'] ?? 'NULL',
                        'st_its_id' => $row['st_its_id'] ?? 'NULL',
                        'st_house' => $row['st_house'], // Either valid ENUM value or null
                        'st_wallet' => $row['st_wallet'] ?? 'NULL',
                        'st_deposit' => $row['st_deposit'] ?? 'NULL',
                        'st_gmail_address' => $row['st_gmail_address'] ?? 'NULL',
                        'st_mobile' => $row['st_mobile_no'] ?? 'NULL',
                        'st_external' => $row['st_external'] ?? 'NULL',
                        'st_on_roll' => $row['st_on_roll'] ?? 'NULL',
                        'st_year_of_admission' => $row['st_year_of_admission'] ?? 'NULL',
                        'st_admitted' => $row['st_admitted'] ?? 'NULL',
                        'st_admitted_class' => $row['st_admitted_class'] ?? 'NULL',
                        'st_flag' => $row['flag'] ?? 'NULL',
                    ]
                );

                // $email = $row['st_gmail_address'] ?? ''; // Default to empty string if key is missing
                // $email = trim($email);
                // if (empty($email) || strtolower($email) === 'null') {
                //     $email = $row['st_roll_no'] 
                //     ? $row['st_roll_no'] . ".dummy." . rand(1000, 9999) . time() . "@gmail.com" 
                //     : "default.dummy." . rand(1000, 9999) . time() . "@gmail.com"; // Generate a dummy email
                // }

                // Add password to the users table if it does not exist
                // if (!User::where('email', $row['st_gmail_address'])->exists()) {
                    User::create([
                        'name' => $row['st_first_name'] . ' ' . $row['st_last_name'],
                        // 'email' => $email,
                        'email' => $row['st_gmail_address'] ?? 'NULL',
                        'password' => $row['st_password_hash'],
                        'role' => "student",
                        'username' => $row['st_roll_no'],
                    ]);
                // }

                // Insert into the `student_class` table
                StudentClassModel::updateOrCreate(
                    [
                        'st_id' => $student->id, // Match by `st_id`
                        'ay_id' => $currentAcademicYear->id, // Match by `ay_id`
                    ],
                    [
                        'cg_id' => $row['cg_id'], // Add class group id
                    ]
                );
            }

            return response()->json([
                'message' => 'CSV imported successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'message' => 'Failed to import CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importDetailsCsv(Request $request)
    {
        // Set the execution time to 2 minutes (120 seconds)
        ini_set('max_execution_time', 120);

        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/student_detail.csv');

            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }

            // Truncate the table before import
            StudentDetailsModel::truncate();

            // Fetch the CSV content using file_get_contents
            $csvContentDetails = file_get_contents($csvFilePath);

            // Parse the CSV content using League\Csv
            $csvDetails = Reader::createFromString($csvContentDetails);
            $csvDetails->setHeaderOffset(0); // Set the header offset (first row as headers)
            $recordsCsv = (new Statement())->process($csvDetails);

            foreach ($recordsCsv as $row) {
                // Get `cg_id` from StudentClassModel for the given `st_id`
                $classGroupId = StudentClassModel::where('st_id', $row['st_id'])->value('cg_id');

                $aadhaarNo = is_numeric($row['sd_aadhaar']) ? $row['sd_aadhaar'] : null;

                // Insert or update the student details, matched by `sd_id`
                StudentDetailsModel::updateOrCreate(
                    ['id' => $row['sd_id']], // Match by `id`
                    [
                        'st_id' => $row['st_id'],
                        // 'sch_id' => $row['sch_id'],
                        // 'aadhaar_no' => is_numeric($row['sd_aadhaar']) && !empty($row['sd_aadhaar']) ? $row['sd_aadhaar'] : 0,
                        'aadhaar_no' => $aadhaarNo,
                        'residential_address1' => $row['sd_residential_address_1'] ?? null,
                        'residential_address2' => $row['sd_residential_address_2'] ?? null,
                        'city' => $row['sd_residential_city'] ?? null,
                        'state' => $row['sd_residential_state'] ?? null,
                        'country' => $row['sd_residential_country_id'] ?? null,
                        // 'pincode' => isset($row['sd_residential_pincode']) && $row['sd_residential_pincode'] !== 'NULL' ? $row['sd_residential_pincode'] : '00',
                        'pincode' => is_numeric($row['sd_residential_pincode']) && !empty($row['sd_residential_pincode']) ? $row['sd_residential_pincode'] : 0,
                        'class_group' => $classGroupId ?? 0,
                        'f_name' => $row['sd_father_first_name'] . ' ' . $row['sd_father_last_name'],
                        'f_email' => $row['sd_father_email'] ?? null,
                        'f_contact' => $row['sd_father_mobile'] ?? null,

                        'f_occupation' => in_array($row['sd_father_occupation_status'], ['employed', 'self-employed', 'none']) 
                        ? $row['sd_father_occupation_status'] 
                        : null,

                        // 'f_occupation' => $row['sd_father_occupation_status'] ?? null,
                        'f_business_name' => $row['sd_father_business_name'] ?? null,
                        'f_business_nature' => $row['sd_father_business_nature'] ?? null,
                        'f_business_address1' => $row['sd_father_business_address_1'] ?? null,
                        'f_business_address2' => $row['sd_father_business_address_2'] ?? null,
                        'f_business_city' => $row['sd_father_business_city'] ?? null,
                        'f_business_state' => $row['sd_father_business_state'] ?? null,
                        'f_business_country' => $row['sd_father_business_country_id'] ?? null,
                        'f_business_pincode' => $row['sd_father_business_pincode'] ?? null,
                        'f_employer_name' => $row['sd_father_employment_employer'] ?? null,
                        'f_designation' => $row['sd_father_employment_nature_work_designation'] ?? null,
                        'f_work_address1' => $row['sd_father_employment_address_1'] ?? null,
                        'f_work_address2' => $row['sd_father_employment_address_2'] ?? null,
                        'f_work_city' => $row['sd_father_employment_city'] ?? null,
                        'f_work_state' => $row['sd_father_employment_state'] ?? null,
                        'f_work_country' => $row['sd_father_employment_country_id'] ?? null,
                        'f_work_pincode' => $row['sd_father_employment_pincode'] ?? null,
                        'm_name' => $row['sd_mother_first_name'] . ' ' . $row['sd_mother_last_name'],
                        'm_email' => $row['sd_mother_email'] ?? null,
                        'm_contact' => $row['sd_mother_mobile'] ?? null,

                        'm_occupation' => (function ($occupation) {
                            if ($occupation === 'homemaker') {
                                $occupation = 'home-maker';
                            }
                            return in_array($occupation, ['employed', 'self-employed', 'home-maker']) 
                                ? $occupation 
                                : null;
                        })($row['sd_mother_occupation_status']),

                        // 'm_occupation' => $row['sd_mother_occupation_status'] ?? null,
                        'm_business_name' => $row['sd_mother_business_name'] ?? null,
                        'm_business_nature' => $row['sd_mother_business_nature'] ?? null,
                        'm_business_address1' => $row['sd_mother_business_address_1'] ?? null,
                        'm_business_address2' => $row['sd_mother_business_address_2'] ?? null,
                        'm_business_city' => $row['sd_mother_business_city'] ?? null,
                        'm_business_state' => $row['sd_mother_business_state'] ?? null,
                        'm_business_country' => $row['sd_mother_business_country_id'] ?? null,
                        'm_business_pincode' => $row['sd_mother_business_pincode'] ?? null,
                        'm_employer_name' => $row['sd_mother_employment_employer'] ?? null,
                        'm_designation' => $row['sd_mother_employment_nature_work_designation'] ?? null,
                        'm_work_address1' => $row['sd_mother_employment_address_1'] ?? null,
                        'm_work_address2' => $row['sd_mother_employment_address_2'] ?? null,
                        'm_work_city' => $row['sd_mother_employment_city'] ?? null,
                        'm_work_state' => $row['sd_mother_employment_state'] ?? null,
                        'm_work_country' => $row['sd_mother_employment_country_id'] ?? null,
                        'm_work_pincode' => $row['sd_mother_employment_pincode'] ?? null,
                        'sd_mobile_primary' => $row['sd_mobile_primary'] ?? null,
                        'sd_email_primary' => $row['sd_email_primary'] ?? null,
                        'sd_blood_group' => $row['sd_blood_group'] ?? null,
                    ]
                );
            }

            return response()->json([
                'message' => 'Student details imported successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'message' => 'Failed to import student details CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetch_duplicate(Request $request)
    {
        $request->validate([
            'st_roll_no' => 'required'
        ]);

        $get_student = StudentModel::where('st_roll_no', $request->input('st_roll_no'))->first();

        if (isset($get_student)) {
            return response()->json([
                'message' => 'Duplicate found.',
                'data' => $get_student->makeHidden(['id', 'created_at', 'updated_at']),
                'status' => 'true'
            ], 200);
        }
        else {
            return response()->json([
                'message' => 'No duplicate found.',
                'status' => 'false'
            ], 404);
        }
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:excel,pdf', // Type of export
            'cg_id' => 'required|integer|exists:t_class_groups,id', // Class group ID
            'bohra' => 'required|boolean', // Bohra status (true/false)
            'gender' => 'required|in:M,F', // Gender (M or F)
            'ay_id' => 'required|integer|exists:t_academic_years,id', // Academic Year ID
        ]);

        try {
            // Fetch and filter data based on the provided parameters
            $data = StudentClassModel::with(['student', 'classGroup', 'academicYear'])
                ->where('cg_id', $validated['cg_id'])
                ->whereHas('student', function ($query) use ($validated) {
                    $query->where('st_bohra', $validated['bohra'])
                        ->where('st_gender', $validated['gender']);
                })
                ->where('ay_id', $validated['ay_id'])
                ->get()
                ->map(function ($studentClass, $index) {
                    $student = $studentClass->student;

                    if (!$student) {
                        return [];
                    }

                    return [
                        'SN' => $index + 1,
                        'Roll No' => $student->st_roll_no,
                        'Name' => $student->st_first_name . ' ' . $student->st_last_name,
                        'Class' => $studentClass->classGroup->cg_name ?? 'Class group not found',
                        'Gender' => $student->st_gender === 'M' ? 'Male' : ($student->st_gender === 'F' ? 'Female' : 'Not Specified'),
                        'DOB' => $student->st_dob ? \Carbon\Carbon::parse($student->st_dob)->format('d-m-Y') : 'N/A',
                        'ITS' => $student->st_its_id ?? 'N/A',
                        'Mobile' => $student->st_mobile ?? 'N/A',
                        'Bohra' => $student->st_bohra ? 'Yes' : 'No',
                        'Academic Year' => $studentClass->academicYear->ay_name ?? 'N/A', // Use related name
                        // 'Class Group ID' => $studentClass->cg_id ?? 'N/A',
                    ];
                })
                ->filter() // Remove empty rows where student data is missing
                ->values()
                ->toArray();

            if (empty($data)) {
                return response()->json(['message' => 'No data available for export.'], 404);
            }

            // Export as Excel
            if ($validated['type'] === 'excel') {
                return $this->exportExcel($data);
            }

            // Export as PDF
            return $this->exportPdf($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to export data.', 'error' => $e->getMessage()], 500);
        }
    }

    private function exportExcel(array $data)
    {
        $fileName = 'Students_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        // return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentsExport($data), $fileName);
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentsExport($data), $fileName, \Maatwebsite\Excel\Excel::XLSX);
    }

    private function exportPdf(array $data)
    {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_header' => 10,
            'margin_footer' => 10,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $mpdf->SetTitle('Student Export');

        // Split HTML into smaller chunks and process each chunk
        $html = view('exports.students_pdf', compact('data'))->render();
        $chunks = str_split($html, 50000); // Split the HTML into chunks of 50,000 characters

        foreach ($chunks as $chunk) {
            $mpdf->WriteHTML($chunk);
        }

        $fileName = 'Students_export_' . now()->format('Y_m_d_H_i_s') . '.pdf';

        return response()->streamDownload(function () use ($mpdf) {
            $mpdf->Output();
        }, $fileName);
    }

    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'receipt' => 'nullable|string',
            'against_fees' => 'required|integer',
            'st_id' => 'required|integer',
        ]);

        try {
            // Manually create an instance of RazorpayService
            $razorpayService = new RazorpayService();

            // Call the createOrder method
            $order = $razorpayService->createOrder(
                $validated['amount'],
                'INR',
                $validated['receipt'] ?? null,
                $validated['against_fees'],
                $validated['st_id']
            );

            return response()->json([
                'success' => true,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchStudentFees(Request $request)
    {
        // Increase PHP memory limit
        ini_set('memory_limit', '1024M');

        // Step 1: Fetch the most recent academic year ID
        $recentAcademicYearId = AcademicYearModel::where('ay_current', 1)->value('id');

        if (!$recentAcademicYearId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active academic year found.'
            ]);
        }

        // Step 2: Get pagination parameters from request
        $page = $request->input('page', 1); // Default to page 1
        $limit = $request->input('limit', 500); // Default to 5 records per page
        $offset = ($page - 1) * $limit;

        // Step 3: Fetch only the student IDs related to the current academic year
        $studentIds = StudentClassModel::where('ay_id', $recentAcademicYearId)
            ->pluck('st_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No students found for the current academic year.'
            ]);
        }

        // Step 4: Fetch fees data for the filtered students
        $feesQuery = FeeModel::with([
            'student:id,st_first_name,st_last_name,st_roll_no', // Fetch only required student fields
            'studentClass.classGroup:id,cg_name'               // Fetch only required class group fields
        ])
            ->whereIn('st_id', $studentIds);

        $totalCount = $feesQuery->count(); // Total records matching the criteria

        $fees = $feesQuery
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($fee) {
                return [
                    'name' => $fee->student
                        ? $fee->student->st_first_name . ' ' . $fee->student->st_last_name
                        : 'N/A', // Concatenate first and last name
                    'roll_no' => $fee->student->st_roll_no ?? 'N/A',
                    'class' => $fee->studentClass->classGroup->cg_name ?? 'N/A',
                    'fee_name' => $fee->fpp_name,
                    'fee_amount' => $fee->fpp_amount,
                    'due_date' => $fee->fpp_due_date,
                    'late_fee_applicable' => $fee->f_late_fee_applicable ? 'Yes' : 'No',
                    'total_amount' => $fee->fpp_amount,
                ];
            });

        // Step 5: Return the processed data along with metadata for pagination
        return response()->json([
            'status' => 'success',
            'data' => $fees,
            'count' => $fees->count(),
            'total' => $totalCount,
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit)
        ]);
    }

    public function migrateUploadsFromCsv()
    {
        $csvFilePath = storage_path('app/public/student_pic.csv'); // Path to the CSV file
        $uploadsDir = public_path('storage/uploads/students/student_profile_images'); // Directory with the files

        // Check if the CSV file exists
        if (!File::exists($csvFilePath)) {
            return response()->json(['status' => 'error', 'message' => 'CSV file not found.']);
        }

        // Truncate the table before import
        UploadModel::truncate();

        // Read the CSV file
        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0); // First row as header

        $records = $csv->getRecords(); // Get all records as an iterator
        $migrated = 0;
        $errors = [];

        foreach ($records as $index => $row) {
            try {
                // Process only active records
                if ((int)$row['active'] !== 1) {
                    continue;
                }

                // Fetch file details
                $filePath = $uploadsDir . '/' . $row['bsp_filename'];
                
                // Skip if the file doesn't exist
                if (!File::exists($filePath)) {
                    $errors[] = "File not found: {$row['bsp_filename']} (Row: {$index})";
                    continue;
                }

                $fileName = pathinfo($filePath, PATHINFO_FILENAME);
                $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                $fileSize = File::size($filePath);
                $fileUrl = url('storage/uploads/students/student_profile_images/' . $row['bsp_filename']);

                // Convert Unix timestamps to datetime format
                $createdAt = isset($row['create_date']) && trim($row['create_date']) !== ''
                    ? date('Y-m-d H:i:s', $row['create_date'])
                    : now();

                $updatedAt = isset($row['modify_date']) && trim($row['modify_date']) !== ''
                    ? date('Y-m-d H:i:s', $row['modify_date'])
                    : $createdAt;

                // Map st_id to student_id in uploads table
                $student = StudentModel::find($row['st_id']);
                if (!$student) {
                    $errors[] = "Student ID not found for st_id: {$row['st_id']} (Row: {$index})";
                    continue;
                }

                // Insert into the new uploads table
                $upload = UploadModel::create([
                    'file_name' => $fileName,
                    'file_ext' => $fileExtension,
                    'file_url' => $fileUrl,
                    'file_size' => $fileSize,
                    // 'student_id' => $student->id, // Map student table ID
                    // 'photo_id' => $row['bsp_id'],  // Store `bsp_id` as `photo_id`
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                ]);

                // Update `photo_id` column in the student table with the ID of the upload record
                $student->update(['photo_id' => $upload->id]);

                $migrated++;
            } catch (\Exception $e) {
                $errors[] = "Error migrating row {$index}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Migration completed. {$migrated} files migrated.",
            'errors' => $errors
        ]);
    }

    public function upgrade_student(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'array', // Ensure it's an array
                'min:1', // Ensure at least one student ID is provided
            ],
            'student_id.*' => [
                'numeric', // Each element should be numeric
                'min:1', // Each ID should be at least 1
                Rule::exists('t_students', 'id'), // Check if each ID exists in the t_students table
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('t_class_groups', 'id'), // Check if the class_id exists in the t_class_groups table
            ],
        ]);
        

        // Use ternary operator to return response
        // return $validator->fails()
        // ? response()->json([
        //     'status' => 'error',
        //     'message' => 'Validation failed.',
        //     'errors' => $validator->errors(),
        // ], 422)
        // : response()->json([
        //     'status' => 'success',
        //     'message' => 'Validation passed successfully.',
        // ], 200);
        return response()->json([
            'status' => 'success',
            'message' => 'Validation passed successfully.',
            'data' => $validated,
        ], 200);
    }

    public function apply_fee_plan(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'array', // Ensure it's an array
                'min:1', // Ensure at least one student ID is provided
            ],
            'student_id.*' => [
                'numeric', // Each element should be numeric
                'min:1', // Each ID should be at least 1
                Rule::exists('t_students', 'id'), // Check if each ID exists in the t_students table
            ],
            'fee_plans_id' => [
                'required',
                'integer',
                Rule::exists('t_fee_plans', 'id'), // Check if the class_id exists in the t_class_groups table
            ],
        ]);
        

        // Use ternary operator to return response
        // return $validator->fails()
        // ? response()->json([
        //     'status' => 'error',
        //     'message' => 'Validation failed.',
        //     'errors' => $validator->errors(),
        // ], 422)
        // : response()->json([
        //     'status' => 'success',
        //     'message' => 'Validation passed successfully.',
        // ], 200);
        return response()->json([
            'status' => 'success',
            'message' => 'Validation passed successfully.',
            'data' => $validated,
        ], 200);
    }

}
