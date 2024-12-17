<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            'quallification' => 'required|string|max:255',
        ]);

        try {
            // Create the teacher
            $teacher = TeacherModel::create($validated);

            // Return success response
            return response()->json([
                'message' => 'Teacher created successfully',
                'data' => $teacher->makeHidden(['id', 'created_at', 'updated_at'])
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
            'email' => 'sometimes|email|unique:teachers,email,' . $id, // Unique email except for the current teacher
            'gender' => 'nullable|in:M,F',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-,Rare',
            'is_class_teacher' => 'nullable|in:0,1',
            'degree' => 'sometimes|string|max:255',
            'quallification' => 'sometimes|string|max:255',
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
            $teacher->update($validated);

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
                    'data' => $teachers,
                    'count' => $teachers->count()
                ], 200)
                : response()->json(['message' => 'No teachers available.'], 400);
        }
    }

    // Delete a teacher
    public function destroy($id)
    {
        try {
            // Attempt to find the teacher
            $teacher = TeacherModel::findOrFail($id);

            // Delete the teacher
            $teacher->delete();

            return response()->json([
                'message' => 'Teacher deleted successfully!'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If teacher not found, return a 404 error
            return response()->json([
                'message' => 'Teacher not present.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json([
                'message' => 'Failed to delete teacher.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
