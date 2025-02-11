<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherModel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;
use App\Models\User;

class TeacherController extends Controller
{
    //
    // Create Teacher API
    public function create(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'email' => 'required|email|unique:t_teachers,email',
            'gender' => 'nullable|in:M,F',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'is_class_teacher' => 'required|in:0,1',
            'degree' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
        ]);

        try {
            // Create the teacher
            $register_teacher = TeacherModel::create([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
                'email' => $validated['email'],
                'gender' => $validated['gender'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'is_class_teacher' => $validated['is_class_teacher'],
                'degree' => $validated['degree'],
                'qualification' => $validated['qualification'],
            ]);

            // Return success response
            return response()->json([
                'message' => 'Teacher created successfully',
                'data' => $register_teacher->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);

        } catch (\Exception $e) {
            // Handle failure
            return response()->json([
                'message' => 'Teacher creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // update
    public function update(Request $request, $id)
    {
        // Validate the input
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:1000',
            'email' => 'sometimes|email|unique:t_teachers,email,' . $id, // Unique email except for the current teacher
            'gender' => 'nullable|in:M,F',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'is_class_teacher' => 'nullable|in:0,1',
            'degree' => 'sometimes|string|max:255',
            'qualification' => 'sometimes|string|max:255',
        ]);

        try {
            // Find the teacher by ID
            $teacher = TeacherModel::find($id);

            // Check if the teacher exists
            if (!$teacher) {
                return response()->json([
                    'message' => 'Teacher not found.'
                ], 404);
            }

            // Update the teacher record with the validated fields
            $teacher->update([
                'name' => $validated['name'] ?? $teacher->name,
                'address' => $validated['address'] ?? $teacher->address,
                'email' => $validated['email'] ?? $teacher->email,
                'gender' => $validated['gender'] ?? $teacher->gender,
                'dob' => $validated['dob'] ?? $teacher->dob,
                'blood_group' => $validated['blood_group'] ?? $teacher->blood_group,
                'is_class_teacher' => $validated['is_class_teacher'] ?? $teacher->is_class_teacher,
                'degree' => $validated['degree'] ?? $teacher->degree,
                'qualification' => $validated['qualification'] ?? $teacher->qualification,
            ]);

            // Return success response
            return response()->json([
                'message' => 'Teacher updated successfully',
                'data' => $teacher->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Exception $e) {
            // Handle failure
            return response()->json([
                'message' => 'Teacher update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all teachers or a specific teacher
    public function index($id = null)
    {
        if ($id) {
            // Fetch a specific teacher
            $teacher = TeacherModel::find($id);

            if ($teacher) {
                // Hide unnecessary fields
                $teacher->makeHidden(['id', 'created_at', 'updated_at']);

                return response()->json([
                    'message' => 'Teacher fetched successfully!',
                    'data' => $teacher
                ], 200);
            }

            return response()->json(['message' => 'Teacher not found.'], 404);
        } else {
            // Fetch all teachers
            $teachers = TeacherModel::all();

            // Hide fields for each teacher
            $teachers->each(function ($teacher) {
                $teacher->makeHidden(['id', 'created_at', 'updated_at']);
            });

            return $teachers->isNotEmpty()
                ? response()->json([
                    'message' => 'Teachers fetched successfully!',
                    // 'data' => $teachers,
                    'data' => array_slice($teachers->toArray(), 0, 10),
                    // 'count' => $teachers->count()
                ], 200)
                : response()->json(['message' => 'No teachers available.'], 400);
        }
    }

    // Delete a teacher
    public function destroy($id)
    {
        try {
            // Attempt to find the teacher
            $teacher = TeacherModel::find($id);
    
            // Check if the teacher exists
            if (!$teacher) {
                return response()->json([
                    'message' => 'Teacher not present.'
                ], 404);
            }
    
            // Perform delete
            $teacher->delete();
    
            return response()->json([
                'message' => 'Teacher deleted successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            // Handle unexpected exceptions
            return response()->json([
                'message' => 'Failed to delete teacher.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function importCsv()
    {
        $csvFilePath = storage_path('app/public/records_teachers.csv'); // Path to your CSV file

        // Check if the CSV file exists
        if (!File::exists($csvFilePath)) {
            return response()->json(['status' => 'error', 'message' => 'CSV file not found.']);
        }

        // Truncate the table before import
        TeacherModel::truncate();

        // Pre-hash the password to use for all users
        $hashedPassword = bcrypt('Saifeeschool'); // Pre-hashed password for performance

        // Read the CSV file
        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0); // Use the first row as the header

        $records = $csv->getRecords(); // Get all records as an iterator
        $insertedTeachers = 0;
        $errors = [];

        foreach ($records as $index => $row) {
            try {
                // Check for duplicate email in TeacherModel
                if (TeacherModel::where('email', $row['Email (username)'])->exists()) {
                    $errors[] = "Duplicate email: {$row['Email (username)']} (Row: {$index})";
                    continue;
                }

                // Insert into TeacherModel
                $teacherRecord = TeacherModel::create([
                    'id' => $row['SN'],
                    'name' => $row['Name'],
                    'email' => $row['Email (username)']
                ]);

                // Check for duplicate email in User model
                if (User::where('email', $row['Email (username)'])->exists()) {
                    $errors[] = "Duplicate user email: {$row['Email (username)']} (Row: {$index})";
                    continue;
                }

                // Insert into User model
                $userRecord = User::create([
                    'name' => $row['Name'],
                    'email' => $row['Email (username)'],
                    'username' => $row['Email (username)'],
                    // 'password' => Hash::make('password') // Default password; change as needed
                    'password' => $hashedPassword, // Use pre-hashed password
                    'role' => 'teacher',
                ]);

                // Assign the 'teacher' role
                // $userRecord->assignRole('teacher');

                $insertedTeachers++;
            } catch (\Exception $e) {
                $errors[] = "Error processing row {$index}: {$e->getMessage()}";
            }
        }

        // Return the summary of the operation
        return response()->json([
            'status' => 'success',
            'message' => "{$insertedTeachers} teachers imported successfully.",
            'errors' => $errors
        ]);
    }

    public function getTeacherNames()
{
    try {
        // Fetch only teacher IDs and names
        $teachers = TeacherModel::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Teacher names fetched successfully!',
            'data' => $teachers,
            'count' => $teachers->count(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching teacher names.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
