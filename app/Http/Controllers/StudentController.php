<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentModel;
use App\Models\StudentDetailsModel;

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
                'st_gmail_address' => $validated['st_gmail_address'],
                'st_mobile' => $validated['st_mobile'],
                'st_external' => $validated['st_external'],
                'st_on_roll' => $validated['st_on_roll'],
                'st_year_of_admission' => $validated['st_year_of_admission'],
                'st_admitted' => $validated['st_admitted'],
                'st_admitted_class' => $validated['st_admitted_class'],
                'st_flag' => $validated['st_flag'],
            ]);

            // Create student details
            $studentDetails  = StudentDetailsModel::create([
                'st_roll_no' => $register_student->id,
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
                'student' => $student,
                'studentDetails' => $studentDetails,
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
            'st_roll_no' => 'required|string|max:255',
            'st_first_name' => 'required|string|max:255',
            'st_last_name' => 'required|string|max:255',
            'st_gender' => 'required|in:M,F',
            'st_dob' => 'required|date|before:today',
            'st_blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'st_bohra' => 'required|in:0,1',
            'st_its_id' => 'required|string|max:255|unique:students,st_its_id,' . $student->id,
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
            'aadhaar_no' => 'nullable|digits:12|unique:student_details,aadhaar_no,' . $studentDetails->id,
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
        $student = Student::findOrFail($id);
        $student->details()->delete();
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully'], 200);
    }

    // Fetch all students with their details
    public function index($id = null)
    {
        if ($id) {
            // Fetch a specific student with details
            $student = Student::with(['details' => function ($query) {
                $query->makeHidden(['id', 'created_at', 'updated_at']);
            }])
                ->find($id);

            if ($student) {
                $student->makeHidden(['id', 'created_at', 'updated_at']);
            }

            return $student
                ? response()->json(['message' => 'Student fetched successfully!', 'data' => $student], 200)
                : response()->json(['message' => 'Student not found.'], 404);
        } else {
            // Fetch all students with details
            $students = Student::with(['details' => function ($query) {
                $query->makeHidden(['id', 'created_at', 'updated_at']);
            }])
                ->get();

            $students->makeHidden(['id', 'created_at', 'updated_at']);

            return $students->isNotEmpty()
                ? response()->json(['message' => 'Students fetched successfully!', 'data' => $students, 'count' => $students->count()], 200)
                : response()->json(['message' => 'No students available.'], 400);
        }
    }
}