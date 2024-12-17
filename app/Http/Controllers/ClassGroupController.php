<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassGroupModel;

class ClassGroupController extends Controller
{
    //
    // Create a new Class Group
    public function create(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'ay_id' => 'required|integer|min:1',
            'cg_name' => 'required|string|max:255', // e.g., "Nursery", "Class 1"
            'cg_order' => 'required|integer|min:1', // Numeric order of classes
        ]);

        try {
            // Create the class group
            $classGroup = ClassGroupModel::create($validated);

            return response()->json([
                'message' => 'Class group created successfully',
                'data' => $classGroup->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create class group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing Class Group
    public function update(Request $request, $id)
    {
        // Validation rules
        $validated = $request->validate([
            'ay_id' => 'sometimes|integer|min:1',
            'cg_name' => 'sometimes|string|max:255', // Update to "Nursery" or "Class 2"
            'cg_order' => 'sometimes|integer|min:1',
        ]);

        try {
            $classGroup = ClassGroupModel::find($id);

            if (!$classGroup) {
                return response()->json(['message' => 'Class group not found.'], 404);
            }

            // Update fields, fallback to existing data if null
            $classGroup->update([
                'ay_id' => $validated['ay_id'] ?? $classGroup->ay_id,
                'cg_name' => $validated['cg_name'] ?? $classGroup->cg_name,
                'cg_order' => $validated['cg_order'] ?? $classGroup->cg_order,
            ]);

            return response()->json([
                'message' => 'Class group updated successfully',
                'data' => $classGroup->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update class group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all class groups or a specific class group
    public function index($id = null)
    {
        if ($id) {
            // Fetch a specific class group
            $classGroup = ClassGroupModel::find($id);

            if ($classGroup) {
                return response()->json([
                    'message' => 'Class group fetched successfully',
                    'data' => $classGroup->makeHidden(['id', 'created_at', 'updated_at'])
                ], 200);
            }

            return response()->json(['message' => 'Class group not found'], 404);
        } else {
            // Fetch all class groups
            $classGroups = ClassGroupModel::orderBy('cg_order')->get();

            $classGroups->each(function ($group) {
                $group->makeHidden(['id', 'created_at', 'updated_at']);
            });

            return $classGroups->isNotEmpty()
                ? response()->json([
                    'message' => 'Class groups fetched successfully',
                    'data' => $classGroups,
                    'count' => $classGroups->count()
                ], 200)
                : response()->json(['message' => 'No class groups available.'], 400);
        }
    }

    // Delete a class group
    public function destroy($id)
    {
        try {
            $classGroup = ClassGroupModel::find($id);

            if (!$classGroup) {
                return response()->json(['message' => 'Class group not found.'], 404);
            }

            $classGroup->delete();

            return response()->json(['message' => 'Class group deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete class group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
