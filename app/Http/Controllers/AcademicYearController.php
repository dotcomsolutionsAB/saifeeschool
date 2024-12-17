<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYearModel;

class AcademicYearController extends Controller
{
    //
    // Create a new Academic Year
    public function create(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'sch_id' => 'required|string|max:10',
            'ay_name' => 'required|string|max:100',
            'ay_start_year' => 'required|string|max:100',
            'ay_start_month' => 'required|string|max:10',
            'ay_end_year' => 'required|string|max:10',
            'ay_end_month' => 'required|string|max:10',
            'ay_current' => 'required|string|in:0,1',
        ]);

        try {
            // Create a new academic year
            $academicYear = AcademicYearModel::create($validated);

            return response()->json([
                'message' => 'Academic year created successfully',
                'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create academic year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing Academic Year
    public function update(Request $request, $id)
    {
        // Validation rules
        $validated = $request->validate([
            'sch_id' => 'sometimes|string|max:10',
            'ay_name' => 'sometimes|string|max:100',
            'ay_start_year' => 'sometimes|string|max:100',
            'ay_start_month' => 'sometimes|string|max:10',
            'ay_end_year' => 'sometimes|string|max:10',
            'ay_end_month' => 'sometimes|string|max:10',
            'ay_current' => 'sometimes|string|in:0,1',
        ]);

        try {
            $academicYear = AcademicYearModel::find($id);

            if (!$academicYear) {
                return response()->json(['message' => 'Academic year not found.'], 404);
            }

            // Update fields, fallback to existing data if null
            $academicYear->update([
                'sch_id' => $validated['sch_id'] ?? $academicYear->sch_id,
                'ay_name' => $validated['ay_name'] ?? $academicYear->ay_name,
                'ay_start_year' => $validated['ay_start_year'] ?? $academicYear->ay_start_year,
                'ay_start_month' => $validated['ay_start_month'] ?? $academicYear->ay_start_month,
                'ay_end_year' => $validated['ay_end_year'] ?? $academicYear->ay_end_year,
                'ay_end_month' => $validated['ay_end_month'] ?? $academicYear->ay_end_month,
                'ay_current' => $validated['ay_current'] ?? $academicYear->ay_current,
            ]);

            return response()->json([
                'message' => 'Academic year updated successfully',
                'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update academic year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all academic years or a specific one
    public function index($id = null)
    {
        if ($id) {
            // Fetch specific record
            $academicYear = AcademicYearModel::find($id);

            if ($academicYear) {
                return response()->json([
                    'message' => 'Academic year fetched successfully',
                    'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
                ], 200);
            }

            return response()->json(['message' => 'Academic year not found'], 404);
        } else {
            // Fetch all records
            $academicYears = AcademicYearModel::all();

            $academicYears->each(function ($year) {
                $year->makeHidden(['id', 'created_at', 'updated_at']);
            });

            return $academicYears->isNotEmpty()
                ? response()->json([
                    'message' => 'Academic years fetched successfully',
                    'data' => $academicYears,
                    'count' => $academicYears->count()
                ], 200)
                : response()->json(['message' => 'No academic years available.'], 400);
        }
    }

    // Delete an Academic Year
    public function destroy($id)
    {
        try {
            $academicYear = AcademicYearModel::find($id);

            if (!$academicYear) {
                return response()->json(['message' => 'Academic year not present.'], 404);
            }

            $academicYear->delete();

            return response()->json(['message' => 'Academic year deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete academic year.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
