<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentClassModel;

class StudentClassController extends Controller
{
    //
    // Create a new Student-Class record
    public function create(Request $request)
    {
        $validated = $request->validate([
            'ay_id' => 'required|integer|min:1',
            'st_id' => 'required|integer|min:1',
            'cg_id' => 'required|integer|min:1',
        ]);

        try {
            $studentClass = StudentClassModel::create($validated);

            return response()->json([
                'message' => 'Student-Class record created successfully',
                'data' => $studentClass->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Student-Class record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing Student-Class record
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'ay_id' => 'sometimes|integer|min:1',
            'st_id' => 'sometimes|integer|min:1',
            'cg_id' => 'sometimes|integer|min:1',
        ]);

        try {
            $studentClass = StudentClassModel::find($id);

            if (!$studentClass) {
                return response()->json(['message' => 'Student-Class record not found.'], 404);
            }

            // Update fields, fallback to existing data if null
            $studentClass->update([
                'ay_id' => $validated['ay_id'] ?? $studentClass->ay_id,
                'st_id' => $validated['st_id'] ?? $studentClass->st_id,
                'cg_id' => $validated['cg_id'] ?? $studentClass->cg_id,
            ]);

            return response()->json([
                'message' => 'Student-Class record updated successfully',
                'data' => $studentClass->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Student-Class record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all Student-Class records or a specific record
    public function index($id = null)
    {
        if ($id) {
            $studentClass = StudentClassModel::find($id);

            if ($studentClass) {
                return response()->json([
                    'message' => 'Student-Class record fetched successfully',
                    'data' => $studentClass->makeHidden(['id', 'created_at', 'updated_at'])
                ], 200);
            }

            return response()->json(['message' => 'Student-Class record not found.'], 404);
        } else {
            $studentClasses = StudentClassModel::all();

            $studentClasses->each(function ($record) {
                $record->makeHidden(['id', 'created_at', 'updated_at']);
            });

            return $studentClasses->isNotEmpty()
                ? response()->json([
                    'message' => 'Student-Class records fetched successfully',
                    'data' => $studentClasses,
                    'count' => $studentClasses->count()
                ], 200)
                : response()->json(['message' => 'No Student-Class records available.'], 400);
        }
    }

    // Delete a Student-Class record
    public function destroy($id)
    {
        try {
            $studentClass = StudentClassModel::find($id);

            if (!$studentClass) {
                return response()->json(['message' => 'Student-Class record not found.'], 404);
            }

            $studentClass->delete();

            return response()->json([
                'message' => 'Student-Class record deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Student-Class record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
