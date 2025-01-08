<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentModel;
use App\Models\StudentDetailsModel;
use App\Models\AcademicYearModel;
use App\Models\ClassGroupModel;
use App\Models\StudentClassModel;
use App\Models\User;
use App\Models\FeeModel;
use App\Models\UploadModel;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Validation\Rule;
use Mpdf\Mpdf;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\RazorpayService;

class StudentController extends Controller
{
    //
    // Create a new student
    public function register(Request $request)
    {
        $validated = $request->validate([
            'st_roll_no' => 'required|string|max:255',
            'st_first_name' => 'required|string|max:255',
            'st_last_name' => 'required|string|max:255',
            'st_gender' => 'required|in:M,F',
            'st_dob' => 'required|date',
            'st_blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'st_bohra' => 'required|in:0,1',
            'st_its_id' => 'required|string|max:255',
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
            'aadhaar_no' => 'required|numeric',
            'residential_address1' => 'required|string|max:1000', // Text field, required
            'residential_address2' => 'nullable|string|max:1000', // Optional text field
            'residential_address3' => 'nullable|string|max:1000', // Optional text field
            'city' => 'required|string|max:255',                 // String, required
            'state' => 'required|string|max:255',                // String, required
            'country' => 'required|string|max:255',              // String, required
            'pincode' => 'required|integer|min:1',               // Integer, required
            'class_group' => 'required|integer|min:1',           // Integer, required
            // Attachment fields
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'aadhaar_card' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'photo_pic' => 'nullable|file|mimes:jpg,png|max:2048',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'f_name' => 'required|string|max:255',
            'f_email' => 'required|email|max:255',
            'f_contact' => 'required|string|max:20',
            'm_name' => 'required|string|max:255',
            'm_email' => 'required|email|max:255',
            'm_contact' => 'required|string|max:20',
            'f_occupation' => 'required|in:employed,self-employed,none',
            'm_occupation' => 'required|in:employed,self-employed,home-maker',
            // Validate father business fields
            'f_business_name' => 'nullable|string|max:255',
            'f_business_nature' => 'nullable|string|max:255',
            'f_business_address1' => 'nullable|string|max:255',
            'f_business_address2' => 'nullable|string|max:255',
            'f_business_city' => 'nullable|string|max:255',
            'f_business_state' => 'nullable|string|max:255',
            'f_business_country' => 'nullable|string|max:255',
            'f_business_pincode' => 'nullable|string|max:10',
            // Validate father work fields
            'f_employer_name' => 'nullable|string|max:255',
            'f_designation' => 'nullable|string|max:255',
            'f_work_address1' => 'nullable|string|max:255',
            'f_work_address2' => 'nullable|string|max:255',
            'f_work_city' => 'nullable|string|max:255',
            'f_work_state' => 'nullable|string|max:255',
            'f_work_country' => 'nullable|string|max:255',
            'f_work_pincode' => 'nullable|string|max:10',
            // Validate mother business fields
            'm_business_name' => 'nullable|string|max:255',
            'm_business_nature' => 'nullable|string|max:255',
            'm_business_address1' => 'nullable|string|max:255',
            'm_business_address2' => 'nullable|string|max:255',
            'm_business_city' => 'nullable|string|max:255',
            'm_business_state' => 'nullable|string|max:255',
            'm_business_country' => 'nullable|string|max:255',
            'm_business_pincode' => 'nullable|string|max:10',
            // Validate mother work fields
            'm_employer_name' => 'nullable|string|max:255',
            'm_designation' => 'nullable|string|max:255',
            'm_work_address1' => 'nullable|string|max:255',
            'm_work_address2' => 'nullable|string|max:255',
            'm_work_city' => 'nullable|string|max:255',
            'm_work_state' => 'nullable|string|max:255',
            'm_work_country' => 'nullable|string|max:255',
            'm_work_pincode' => 'nullable|string|max:10',
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
                'f_business_pincode' => 'required|string|max:10',
            ]));
            // Clear work-related fields
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
                'f_work_pincode' => 'required|string|max:10',
            ]));
            // Clear business-related fields
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
                'm_business_pincode' => 'required|string|max:10',
            ]));
            // Clear work-related fields
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
                'm_work_pincode' => 'required|string|max:10',
            ]));
            // Clear business-related fields
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

            if ($request->hasFile('photo_pic')) {
                $photoFile = $request->file('photo_pic');
                $photoPath = $photoFile->store('uploads/students/student_profile_images', 'public');
                $photoId = UploadModel::create([
                    'file_name' => pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $photoFile->getClientOriginalExtension(),
                    'file_url' => $photoPath,
                    'file_size' => $photoFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('birth_certificate')) {
                $birthCertificateFile = $request->file('birth_certificate');
                $birthCertificatePath = $birthCertificateFile->store('uploads/students/birth_certificates', 'public');
                $birthCertificateId = UploadModel::create([
                    'file_name' => pathinfo($birthCertificateFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $birthCertificateFile->getClientOriginalExtension(),
                    'file_url' => $birthCertificatePath,
                    'file_size' => $birthCertificateFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('aadhaar_card')) {
                $aadhaarFile = $request->file('aadhaar_card');
                $aadhaarPath = $aadhaarFile->store('uploads/students/aadhaar_certificate', 'public');
                $aadhaarId = UploadModel::create([
                    'file_name' => pathinfo($aadhaarFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $aadhaarFile->getClientOriginalExtension(),
                    'file_url' => $aadhaarPath,
                    'file_size' => $aadhaarFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('attachment')) {
                $attachmentFile = $request->file('attachment');
                $attachmentPath = $aadhaarFile->store('uploads/students/attachment', 'public');
                $attachmentId = UploadModel::create([
                    'file_name' => pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $attachmentFile->getClientOriginalExtension(),
                    'file_url' => $attachmentPath,
                    'file_size' => $attachmentFile->getSize(),
                ])->id;
            }

            // Create student
            $register_student = StudentModel::create([
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
                'st_gmail_address' => strtolower($validated['st_gmail_address']),
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

            // register on user
            $register_user = User::create([
                'name' => trim(($validated['st_first_name'] ?? '') . ' ' . ($validated['st_last_name'] ?? '')),
                'email' => strtolower($validated['st_gmail_address']),
                'password' => bcrypt($validated['st_roll_no']),
                'role' => "student",
                'username' => $validated['st_gmail_address'],
            ]);

            // Create student details
            $studentDetails  = StudentDetailsModel::create([
                'st_id' => $register_student->id,
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
                'f_business_name' => $validated['f_business_name'] ?? null,
                'f_business_nature' => $validated['f_business_nature'] ?? null,
                'f_business_address1' => $validated['f_business_address1'] ?? null,
                'f_business_address2' => $validated['f_business_address2'] ?? null,
                'f_business_city' => $validated['f_business_city'] ?? null,
                'f_business_state' => $validated['f_business_state'] ?? null,
                'f_business_country' => $validated['f_business_country'] ?? null,
                'f_business_pincode' => $validated['f_business_pincode'] ?? null,
                'f_employer_name' => $validated['f_employer_name'] ?? null,
                'f_designation' => $validated['f_designation'] ?? null,
                'f_work_address1' => $validated['f_work_address1'] ?? null,
                'f_work_address2' => $validated['f_work_address2'] ?? null,
                'f_work_city' => $validated['f_work_city'] ?? null,
                'f_work_state' => $validated['f_work_state'] ?? null,
                'f_work_country' => $validated['f_work_country'] ?? null,
                'f_work_pincode' => $validated['f_work_pincode'] ?? null,
                'm_name' => $validated['m_name'] ?? null,
                'm_email' => $validated['m_email'] ?? null,
                'm_contact' => $validated['m_contact'] ?? null,
                'm_occupation' => $validated['m_occupation'] ?? null,
                'm_business_name' => $validated['m_business_name'] ?? null,
                'm_business_nature' => $validated['m_business_nature'] ?? null,
                'm_business_address1' => $validated['m_business_address1'] ?? null,
                'm_business_address2' => $validated['m_business_address2'] ?? null,
                'm_business_city' => $validated['m_business_city'] ?? null,
                'm_business_state' => $validated['m_business_state'] ?? null,
                'm_business_country' => $validated['m_business_country'] ?? null,
                'm_business_pincode' => $validated['m_business_pincode'] ?? null,
                'm_employer_name' => $validated['m_employer_name'] ?? null,
                'm_designation' => $validated['m_designation'] ?? null,
                'm_work_address1' => $validated['m_work_address1'] ?? null,
                'm_work_address2' => $validated['m_work_address2'] ?? null,
                'm_work_city' => $validated['m_work_city'] ?? null,
                'm_work_state' => $validated['m_work_state'] ?? null,
                'm_work_country' => $validated['m_work_country'] ?? null,
                'm_work_pincode' => $validated['m_work_pincode'] ?? null,
            ]);

            return response()->json([
                'message' => 'Student registered successfully',
                'student' => $register_student->makeHidden(['id', 'created_at', 'updated_at']),
                'studentDetails' => $studentDetails->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
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
            'profile_picture' => 'nullable|file|mimes:jpg,png|max:2048',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'aadhaar_no' => 'nullable|digits:12|unique:t_student_details,aadhaar_no,' . $studentDetails->id,
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

            if ($request->hasFile('photo_pic')) {
                $photoFile = $request->file('photo_pic');
                $photoPath = $photoFile->store('uploads/students/student_profile_images', 'public');
                $photoId = UploadModel::create([
                    'file_name' => pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $photoFile->getClientOriginalExtension(),
                    'file_url' => $photoPath,
                    'file_size' => $photoFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('birth_certificate')) {
                $birthCertificateFile = $request->file('birth_certificate');
                $birthCertificatePath = $birthCertificateFile->store('uploads/students/birth_certificates', 'public');
                $birthCertificateId = UploadModel::create([
                    'file_name' => pathinfo($birthCertificateFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $birthCertificateFile->getClientOriginalExtension(),
                    'file_url' => $birthCertificatePath,
                    'file_size' => $birthCertificateFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('aadhaar_card')) {
                $aadhaarFile = $request->file('aadhaar_card');
                $aadhaarPath = $aadhaarFile->store('uploads/students/aadhaar_certificate', 'public');
                $aadhaarId = UploadModel::create([
                    'file_name' => pathinfo($aadhaarFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $aadhaarFile->getClientOriginalExtension(),
                    'file_url' => $aadhaarPath,
                    'file_size' => $aadhaarFile->getSize(),
                ])->id;
            }

            if ($request->hasFile('attachment')) {
                $attachmentFile = $request->file('attachment');
                $attachmentPath = $aadhaarFile->store('uploads/students/attachment', 'public');
                $attachmentId = UploadModel::create([
                    'file_name' => pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_ext' => $attachmentFile->getClientOriginalExtension(),
                    'file_url' => $attachmentPath,
                    'file_size' => $attachmentFile->getSize(),
                ])->id;
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

    // Fetch all students with their details
    // public function index($id = null)
    // {
    //     if ($id) {
    //         // Fetch a specific student with details
    //         $student = StudentModel::with('details')->find($id);
        
    //         if ($student) {
    //             // Hide fields in the main student model
    //             $student->makeHidden(['id', 'created_at', 'updated_at']);
        
    //             // Hide fields in the details relation
    //             if ($student->details) {
    //                 $student->details->makeHidden(['id', 'created_at', 'updated_at']);
    //             }
        
    //             return response()->json([
    //                 'message' => 'Student fetched successfully!',
    //                 'data' => $student
    //             ], 200);
    //         }
        
    //         return response()->json(['message' => 'Student not found.'], 404);
        
    //     } else {
    //         // Fetch all students with details
    //         $students = StudentModel::with('details')->get();
        
    //         // Hide fields in each student model and its details
    //         $students->each(function ($student) {
    //             $student->makeHidden(['id', 'created_at', 'updated_at']);
        
    //             if ($student->details) {
    //                 $student->details->makeHidden(['id', 'created_at', 'updated_at']);
    //             }
    //         });
        
    //         return $students->isNotEmpty()
    //             ? response()->json([
    //                 'message' => 'Students fetched successfully!',
    //                 'data' => $students,
    //                 'count' => $students->count()
    //             ], 200)
    //             : response()->json(['message' => 'No students available.'], 400);
    //     }        
    // }

    // public function index($id = null)
    // {
    //     try {
    //         // Step 1: Get the current academic year
    //         $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //             ->where('ay_current', '1')
    //             ->first();

    //         if (!$currentAcademicYear) {
    //             $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                 ->orderBy('id', 'desc')
    //                 ->first();
    //         }

    //         if (!$currentAcademicYear) {
    //             return response()->json(['message' => 'No academic year records found.'], 404);
    //         }

    //         if ($id) {
    //             // Fetch a specific student's class details in the current academic year
    //             $studentClass = $currentAcademicYear->studentClasses()
    //                 ->where('st_id', $id)
    //                 ->with(['student', 'classGroup'])
    //                 ->first();

    //             if (!$studentClass) {
    //                 return response()->json(['message' => 'Student is not enrolled in the determined academic year.'], 404);
    //             }

    //             return response()->json([
    //                 'message' => 'Student class name fetched successfully.',
    //                 'data' => [
    //                     'student_record' => $studentClass->student->makeHidden(['id', 'created_at', 'updated_at']),
    //                     'academic_year' => $currentAcademicYear->ay_name,
    //                     'class_name' => $studentClass->classGroup->cg_name ?? 'Class group not found',
    //                 ],
    //             ], 200);
    //         } else {
    //             // Fetch all student-class records for the determined academic year
    //             $studentClasses = $currentAcademicYear->studentClasses()->with(['student', 'classGroup'])->get();

    //             if ($studentClasses->isEmpty()) {
    //                 return response()->json(['message' => 'No students enrolled in the determined academic year.'], 404);
    //             }

    //             // Map the data to include student details and class names
    //             $data = $studentClasses->map(function ($studentClass) use ($currentAcademicYear) {
    //                 return [
    //                     'student_record' => $studentClass->student ? $studentClass->student->makeHidden(['id', 'created_at', 'updated_at']) : 'Student not found',
    //                     'academic_year' => $currentAcademicYear->ay_name,
    //                     'class_name' => $studentClass->classGroup->cg_name ?? 'Class group not found',
    //                 ];
    //             });

    //             return response()->json([
    //                 'message' => 'Student class names fetched successfully.',
    //                 'academic_year' => $currentAcademicYear->ay_name,
    //                 'data' => $data,
    //             ], 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while fetching student class names.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function index($id = null)
    // {
    //     try {

    //         // Validate the request for optional `ay_id`
    //         $validated = $request->validate([
    //             'ay_id' => 'nullable|integer|exists:t_academic_years,id',
    //         ]);

    //         // Determine the academic year to use
    //         $currentAcademicYear = null;
    //         if (!empty($validated['ay_id'])) {
    //             $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                 ->where('id', $validated['ay_id'])
    //                 ->first();
    //         }
    //         else {

    //             // Step 1: Get the current academic year
    //             $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                 ->where('ay_current', '1')
    //                 ->first();

    //             if (!$currentAcademicYear) {
    //                 $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                     ->orderBy('id', 'desc')
    //                     ->first();
    //             }

    //         }

    //         if (!$currentAcademicYear) {
    //             return response()->json(['message' => 'No academic year records found.'], 404);
    //         }

    //         if ($id) {
    //             // Fetch a specific student's class details in the current academic year
    //             $studentClass = $currentAcademicYear->studentClasses()
    //                 ->where('st_id', $id)
    //                 ->with(['student', 'classGroup'])
    //                 ->first();

    //             if (!$studentClass) {
    //                 return response()->json(['message' => 'Student is not enrolled in the determined academic year.'], 404);
    //             }

    //             // Format the student data
    //             $studentData = $studentClass->student->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
    //             $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //             $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //             $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';

    //             return response()->json([
    //                 'message' => 'Student class name fetched successfully.',
    //                 'data' => $studentData,
    //             ], 200);
    //         } else {
    //             // Fetch all student-class records for the determined academic year
    //             $studentClasses = $currentAcademicYear->studentClasses()->with(['student', 'classGroup'])->get();

    //             if ($studentClasses->isEmpty()) {
    //                 return response()->json(['message' => 'No students enrolled in the determined academic year.'], 404);
    //             }

    //             // Map the data to include student details and class names
    //             $data = $studentClasses->map(function ($studentClass) {
    //                 $studentData = $studentClass->student ? $studentClass->student->makeHidden(['id', 'created_at', 'updated_at'])->toArray() : [];
    //                 if (!empty($studentData)) {
    //                     $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //                     $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //                 }
    //                 $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
    //                 return $studentData;
    //             });

    //             return response()->json([
    //                 'message' => 'Student class names fetched successfully.',
    //                 'academic_year' => $currentAcademicYear->ay_name,
    //                 'data' => $data,
    //             ], 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while fetching student class names.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function index(Request $request, $id = null)
    // {
    //     try {
    //         // Validate the request for optional `ay_id`
    //         $validated = $request->validate([
    //             'ay_id' => 'nullable|integer|exists:t_academic_years,id',
    //         ]);

    //         // Determine the academic year to use
    //         $currentAcademicYear = null;
    //         if (!empty($validated['ay_id'])) {
    //             $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                 ->where('id', $validated['ay_id'])
    //                 ->first();
    //         } else {
    //             $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                 ->where('ay_current', '1')
    //                 ->first();

    //             if (!$currentAcademicYear) {
    //                 $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
    //                     ->orderBy('id', 'desc')
    //                     ->first();
    //             }
    //         }

    //         if (!$currentAcademicYear) {
    //             return response()->json(['message' => 'No academic year records found.'], 404);
    //         }

    //         if ($id) {
    //             // Fetch a specific student's class details in the determined academic year
    //             $studentClass = $currentAcademicYear->studentClasses()
    //                 ->where('st_id', $id)
    //                 ->with(['student', 'classGroup'])
    //                 ->first();

    //             if (!$studentClass) {
    //                 return response()->json(['message' => 'Student is not enrolled in the determined academic year.', 'status' => 'false'], 404);
    //             }

    //             // Format the student data
    //             $studentData = $studentClass->student->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
    //             $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //             $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //             $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';

    //             return response()->json([
    //                 'message' => 'Student class name fetched successfully.',
    //                 'data' => $studentData,
    //                 'status' => 'true'
    //             ], 200);
    //         } else {
    //             // Fetch all student-class records for the determined academic year
    //             $studentClasses = $currentAcademicYear->studentClasses()->with(['student', 'classGroup'])->get();

    //             if ($studentClasses->isEmpty()) {
    //                 return response()->json(['message' => 'No students enrolled in the determined academic year.',  'status' => 'false'], 404);
    //             }

    //             // Map the data to include student details and class names
    //             $data = $studentClasses->map(function ($studentClass) {
    //                 $studentData = $studentClass->student ? $studentClass->student->makeHidden(['id', 'created_at', 'updated_at'])->toArray() : [];
    //                 if (!empty($studentData)) {
    //                     $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
    //                     $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
    //                 }
    //                 $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
    //                 return $studentData;
    //             });

    //             return response()->json([
    //                 'message' => 'Student class names fetched successfully.',
    //                 'academic_year' => $currentAcademicYear->ay_name,
    //                 'data' => $data,
    //                 'status' => 'true'
    //             ], 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while fetching student class names.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index(Request $request, $id = null)
{
    try {
        // Validate the request for optional `ay_id`
        $validated = $request->validate([
            'ay_id' => 'nullable|integer|exists:t_academic_years,id',
        ]);

        // Determine the academic year to use
        $currentAcademicYear = null;
        if (!empty($validated['ay_id'])) {
            $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
                ->where('id', $validated['ay_id'])
                ->first();
        } else {
            $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
                ->where('ay_current', '1')
                ->first();

            if (!$currentAcademicYear) {
                $currentAcademicYear = AcademicYearModel::with(['studentClasses.student', 'studentClasses.classGroup'])
                    ->orderBy('id', 'desc')
                    ->first();
            }
        }

        if (!$currentAcademicYear) {
            return response()->json(['message' => 'No academic year records found.'], 404);
        }

        if ($id) {
            // Fetch a specific student's class details in the determined academic year
            $studentClass = $currentAcademicYear->studentClasses()
                ->where('st_id', $id)
                ->with(['student', 'classGroup'])
                ->first();

            if (!$studentClass) {
                return response()->json(['message' => 'Student is not enrolled in the determined academic year.', 'status' => 'false'], 404);
            }

            // Fetch student details and photo
            $student = $studentClass->student;
            $photo = $student->photo_id
                ? UploadModel::where('id', $student->photo_id)->value('file_url')
                : null;

            // Format the student data
            $studentData = $student->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
            $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
            $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
            $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
            $studentData['photo'] = $photo;

            return response()->json([
                'message' => 'Student class name fetched successfully.',
                'data' => $studentData,
                'status' => 'true'
            ], 200);
        } else {
            // Fetch all student-class records for the determined academic year
            $studentClasses = $currentAcademicYear->studentClasses()->with(['student', 'classGroup'])->get();

            if ($studentClasses->isEmpty()) {
                return response()->json(['message' => 'No students enrolled in the determined academic year.',  'status' => 'false'], 404);
            }

            // Map the data to include student details, class names, and photos
            $data = $studentClasses->map(function ($studentClass) {
                $student = $studentClass->student;

                if (!$student) {
                    return null;
                }

                $photo = $student->photo_id
                    ? UploadModel::where('id', $student->photo_id)->value('file_url')
                    : null;

                $studentData = $student->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
                $studentData['st_gender'] = $studentData['st_gender'] === 'M' ? 'Male' : ($studentData['st_gender'] === 'F' ? 'Female' : null);
                $studentData['st_dob'] = $studentData['st_dob'] ? \Carbon\Carbon::parse($studentData['st_dob'])->format('d-m-Y') : null;
                $studentData['class_name'] = $studentClass->classGroup->cg_name ?? 'Class group not found';
                $studentData['photo'] = $photo;

                return $studentData;
            })->filter();

            return response()->json([
                'message' => 'Student class names fetched successfully.',
                'academic_year' => $currentAcademicYear->ay_name,
                'data' => $data,
                'status' => 'true'
            ], 200);
        }
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while fetching student class names.',
            'error' => $e->getMessage(),
        ], 500);
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

}
