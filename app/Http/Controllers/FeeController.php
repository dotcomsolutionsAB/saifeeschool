<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeeModel;

class FeeController extends Controller
{
    //
     // Fetch all records or a specific record by ID
     public function index($id = null)
     {
         if ($id) {
             $studentFee = FeeModel::find($id);
 
             if ($studentFee) {
                 return response()->json([
                     'message' => 'Student fee record fetched successfully!',
                     'data' => $studentFee->makeHidden(['created_at', 'updated_at'])
                 ], 200);
             }
 
             return response()->json(['message' => 'Student fee record not found.'], 404);
         }
 
         $studentFees = FeeModel::all()->makeHidden(['created_at', 'updated_at']);
 
         return $studentFees->isNotEmpty()
             ? response()->json([
                 'message' => 'Student fee records fetched successfully!',
                 'data' => $studentFees,
                 'count' => $studentFees->count()
             ], 200)
             : response()->json(['message' => 'No student fee records available.'], 400);
     }
 
     // Create a new record
     public function register(Request $request)
     {
         $validated = $request->validate([
             'st_id' => 'nullable|integer',
             'st_roll_no' => 'required|string|max:100',
             'fpp_id' => 'nullable|integer',
             'cg_id' => 'required|string|max:10',
             'ay_id' => 'nullable|integer',
             'fpp_name' => 'nullable|string',
             'fpp_due_date' => 'nullable|integer',
             'fpp_month_no' => 'nullable|integer|min:1|max:12',
             'fpp_year_no' => 'nullable|integer|min:2000',
             'fpp_amount' => 'required|numeric|min:0',
             'f_concession' => 'nullable|numeric|min:0',
             'fpp_late_fee' => 'nullable|numeric|min:0',
             'f_late_fee_applicable' => 'nullable|in:0,1',
             'f_late_fee_paid' => 'nullable|numeric|min:0',
             'f_total_paid' => 'nullable|numeric|min:0',
             'f_paid' => 'nullable|in:0,1',
             'f_paid_date' => 'nullable|integer',
             'f_active' => 'nullable|in:0,1',
             'fp_recurring' => 'nullable|in:0,1',
             'fp_main_monthly_fee' => 'nullable|in:0,1',
             'fp_main_admission_fee' => 'nullable|in:0,1',
         ]);
 
         try {
             $studentFee = FeeModel::create($validated);
 
             return response()->json([
                 'message' => 'Student fee record created successfully!',
                 'data' => $studentFee->makeHidden(['created_at', 'updated_at'])
             ], 201);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to create student fee record.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // Update a specific record
     public function update(Request $request, $id)
     {
         $studentFee = FeeModel::find($id);
 
         if (!$studentFee) {
             return response()->json(['message' => 'Student fee record not found.'], 404);
         }
 
         $validated = $request->validate([
             'st_id' => 'sometimes|integer',
             'st_roll_no' => 'sometimes|string|max:100',
             'fpp_id' => 'sometimes|integer',
             'cg_id' => 'sometimes|string|max:10',
             'ay_id' => 'sometimes|integer',
             'fpp_name' => 'sometimes|string',
             'fpp_due_date' => 'sometimes|integer',
             'fpp_month_no' => 'sometimes|integer|min:1|max:12',
             'fpp_year_no' => 'sometimes|integer|min:2000',
             'fpp_amount' => 'sometimes|numeric|min:0',
             'f_concession' => 'sometimes|numeric|min:0',
             'fpp_late_fee' => 'sometimes|numeric|min:0',
             'f_late_fee_applicable' => 'sometimes|in:0,1',
             'f_late_fee_paid' => 'sometimes|numeric|min:0',
             'f_total_paid' => 'sometimes|numeric|min:0',
             'f_paid' => 'sometimes|in:0,1',
             'f_paid_date' => 'sometimes|integer',
             'f_active' => 'sometimes|in:0,1',
             'fp_recurring' => 'sometimes|in:0,1',
             'fp_main_monthly_fee' => 'sometimes|in:0,1',
             'fp_main_admission_fee' => 'sometimes|in:0,1',
         ]);
 
         try {
             $studentFee->update($validated);
 
             return response()->json([
                 'message' => 'Student fee record updated successfully!',
                 'data' => $studentFee->makeHidden(['created_at', 'updated_at'])
             ], 200);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to update student fee record.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // Delete a specific record
     public function destroy($id)
     {
         $studentFee = FeeModel::find($id);
 
         if (!$studentFee) {
             return response()->json(['message' => 'Student fee record not found.'], 404);
         }
 
         try {
             $studentFee->delete();
 
             return response()->json(['message' => 'Student fee record deleted successfully!'], 200);
         } catch (\Exception $e) {
             return response()->json([
                 'message' => 'Failed to delete student fee record.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
}
