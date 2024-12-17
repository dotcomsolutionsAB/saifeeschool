<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeePlanParticularModel;

class FeePlanPeriodController extends Controller
{
    //
     // Fetch all records or a specific record by ID
     public function index($id = null)
     {
         if ($id) {
             $feePlanParticular = FeePlanParticularModel::find($id);
 
             if ($feePlanParticular) {
                 return response()->json([
                     'message' => 'Fee plan particular fetched successfully!',
                     'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at'])
                 ], 200);
             }
 
             return response()->json(['message' => 'Fee plan particular not found.'], 404);
         }
 
         $feePlanParticulars = FeePlanParticularModel::all()->makeHidden(['created_at', 'updated_at']);
 
         return $feePlanParticulars->isNotEmpty()
             ? response()->json([
                 'message' => 'Fee plan particulars fetched successfully!',
                 'data' => $feePlanParticulars,
                 'count' => $feePlanParticulars->count()
             ], 200)
             : response()->json(['message' => 'No fee plan particulars available.'], 400);
     }
 
     // Create a new record
     public function register(Request $request)
     {
         $validated = $request->validate([
             'fp_id' => 'required|integer',
             'ay_id' => 'required|integer',
             'fpp_name' => 'required|string|max:255',
             'fpp_amount' => 'required|numeric|min:0',
             'fpp_late_fee' => 'required|string|max:100',
             'fpp_due_date' => 'required|date',
             'fpp_month_no' => 'required|integer|min:1|max:12',
             'fpp_year_no' => 'required|integer|min:2000',
             'fpp_order_no' => 'required|string|max:100',
         ]);
 
         try {
             $feePlanParticular = FeePlanParticularModel::create($validated);
 
             return response()->json([
                 'message' => 'Fee plan particular created successfully!',
                 'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at'])
             ], 201);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to create fee plan particular.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // Update a specific record
     public function update(Request $request, $id)
     {
         $feePlanParticular = FeePlanParticularModel::find($id);
 
         if (!$feePlanParticular) {
             return response()->json(['message' => 'Fee plan particular not found.'], 404);
         }
 
         $validated = $request->validate([
             'fp_id' => 'sometimes|integer',
             'ay_id' => 'sometimes|integer',
             'fpp_name' => 'sometimes|string|max:255',
             'fpp_amount' => 'sometimes|numeric|min:0',
             'fpp_late_fee' => 'sometimes|string|max:100',
             'fpp_due_date' => 'sometimes|date',
             'fpp_month_no' => 'sometimes|integer|min:1|max:12',
             'fpp_year_no' => 'sometimes|integer|min:2000',
             'fpp_order_no' => 'sometimes|string|max:100',
         ]);
 
         try {
             $feePlanParticular->update($validated);
 
             return response()->json([
                 'message' => 'Fee plan particular updated successfully!',
                 'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at'])
             ], 200);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to update fee plan particular.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // Delete a specific record
     public function destroy($id)
     {
         $feePlanParticular = FeePlanParticularModel::find($id);
 
         if (!$feePlanParticular) {
             return response()->json(['message' => 'Fee plan particular not found.'], 404);
         }
 
         try {
             $feePlanParticular->delete();
 
             return response()->json(['message' => 'Fee plan particular deleted successfully!'], 200);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to delete fee plan particular.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
}
