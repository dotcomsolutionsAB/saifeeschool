<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeePlanModel;

class FeePlanController extends Controller
{
    //
    // Fetch all records or a specific record by ID
    public function index($id = null)
    {
        if ($id) {
            $feePlan = FeePlanModel::find($id);

            if ($feePlan) {
                return response()->json([
                    'message' => 'Fee plan fetched successfully!',
                    'data' => $feePlan->makeHidden(['created_at', 'updated_at'])
                ], 200);
            }
            return response()->json(['message' => 'Fee plan not found.'], 404);
        }

        $feePlans = FeePlanModel::all()->makeHidden(['created_at', 'updated_at']);

        return $feePlans->isNotEmpty()
            ? response()->json([
                'message' => 'Fee plans fetched successfully!',
                'data' => $feePlans,
                'count' => $feePlans->count()
            ], 200)
            : response()->json(['message' => 'No fee plans available.'], 400);
    }

    // Create a new record
    public function register(Request $request)
    {
        $validated = $request->validate([
            'ay_id' => 'nullable|integer',
            'fp_name' => 'nullable|string|max:1000',
            'fp_recurring' => 'required|in:0,1',
            'fp_main_monthly_fee' => 'required|in:0,1',
            'fp_main_admission_fee' => 'required|in:0,1',
            'cg_id' => 'required|string|max:100',
        ]);

        try {
            $feePlan = FeePlanModel::create($validated);

            return response()->json([
                'message' => 'Fee plan created successfully!',
                'data' => $feePlan->makeHidden(['created_at', 'updated_at'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create fee plan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a specific record
    public function update(Request $request, $id)
    {
        $feePlan = FeePlanModel::find($id);

        if (!$feePlan) {
            return response()->json(['message' => 'Fee plan not found.'], 404);
        }

        $validated = $request->validate([
            'ay_id' => 'nullable|integer',
            'fp_name' => 'nullable|string|max:1000',
            'fp_recurring' => 'sometimes|in:0,1',
            'fp_main_monthly_fee' => 'sometimes|in:0,1',
            'fp_main_admission_fee' => 'sometimes|in:0,1',
            'cg_id' => 'sometimes|string|max:100',
        ]);

        try {
            $feePlan->update([
                'ay_id' => $validated['ay_id'] ?? $feePlan->ay_id,
                'fp_name' => $validated['fp_name'] ?? $feePlan->fp_name,
                'fp_recurring' => $validated['fp_recurring'] ?? $feePlan->fp_recurring,
                'fp_main_monthly_fee' => $validated['fp_main_monthly_fee'] ?? $feePlan->fp_main_monthly_fee,
                'fp_main_admission_fee' => $validated['fp_main_admission_fee'] ?? $feePlan->fp_main_admission_fee,
                'cg_id' => $validated['cg_id'] ?? $feePlan->cg_id,
            ]);

            return response()->json([
                'message' => 'Fee plan updated successfully!',
                'data' => $feePlan->makeHidden(['created_at', 'updated_at'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update fee plan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a specific record
    public function destroy($id)
    {
        $feePlan = FeePlanModel::find($id);

        if (!$feePlan) {
            return response()->json(['message' => 'Fee plan not found.'], 404);
        }

        try {
            $feePlan->delete();

            return response()->json(['message' => 'Fee plan deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete fee plan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }    
}
