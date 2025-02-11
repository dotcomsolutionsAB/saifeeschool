<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeePlanModel;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FeePlanPeriodModel;

class FeePlanController extends Controller
{
    
    public function index(Request $request, $id = null)
    {
        if (!$id) {
            // Validate `ay_id` as required only when `$id` is not provided
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
            ]);

            $ay_id = $validated['ay_id'];

            // Fetch all Fee Plans for the specified `ay_id`
            $feePlans = FeePlanModel::where('ay_id', $ay_id)->get()->makeHidden(['created_at', 'updated_at']);

            return $feePlans->isNotEmpty()
                ? response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Fee plans fetched successfully!',
                    'data' => $feePlans,
                    'count' => $feePlans->count(),
                ])
                : response()->json([
                    'code' => 200,
                    'status' => false,
                    'message' => 'No fee plans available.'
                ]);
        }

        // Fetch a specific Fee Plan by ID, optionally filtered by `ay_id`
        $feePlan = FeePlanModel::where('id', $id)->first();

        if ($feePlan) {
            return response()->json([
                'message' => 'Fee plan fetched successfully!',
                'data' => $feePlan->makeHidden(['created_at', 'updated_at']),
            ], 200);
        }

        return response()->json(['message' => 'Fee plan not found.'], 404);
    }

    public function viewAll(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'type'  => 'nullable|string|in:monthly,admission,one_time,recurring',
            ]);
    
            $ay_id = $validated['ay_id'];
    
            // Fetch latest fee periods for each fee plan in a single query
            $feePeriods = FeePlanPeriodModel::selectRaw('fp_id, MAX(fpp_due_date) as last_due_date, MAX(fpp_amount) as last_fpp_amount, MAX(fpp_order_no) as last_fpp_order_no')
                ->where('ay_id', $ay_id)
                ->groupBy('fp_id')
                ->orderBy('last_fpp_order_no', 'desc')
                ->get()
                ->keyBy('fp_id');
    
            // Fetch all fee plans for the academic year
            $feePlans = FeePlanModel::where('ay_id', $ay_id)
                ->when(!empty($validated['type']), function ($query) use ($validated) {
                    switch ($validated['type']) {
                        case 'monthly':
                            $query->where('fp_recurring', '1')->where('fp_main_monthly_fee', '1');
                            break;
                        case 'admission':
                            $query->where('fp_main_admission_fee', '1');
                            break;
                        case 'one_time':
                            $query->where('fp_recurring', '0')->where('fp_main_monthly_fee', '1');
                            break;
                        case 'recurring':
                            $query->where('fp_recurring', '1')->where('fp_main_monthly_fee', '0');
                            break;
                    }
                })
                ->withCount(['fees as applied_students' => function ($query) {
                    $query->select(DB::raw("COUNT(DISTINCT st_id)"));
                }])
                ->get();
    
            // Map the data into an optimized structure
            $formattedPlans = $feePlans->map(function ($feePlan) use ($feePeriods) {
                $fp_id = $feePlan->id;
                $period = $feePeriods[$fp_id] ?? null;
    
                return [
                    'fp_id' => $fp_id,
                    'fp_name' => $feePlan->fp_name,
                    'last_due_date' => $period ? $period->last_due_date : null,
                    'last_fpp_amount' => $period ? $period->last_fpp_amount : null,
                    'last_fpp_order_no' => $period ? $period->last_fpp_order_no : null,
                    'applied_students' => $feePlan->applied_students,
                ];
            });
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Fee plans with periods fetched successfully!',
                'data' => $formattedPlans,
                'count' => $formattedPlans->count(),
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching fee plans with periods.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Create a new record
    public function register(Request $request)
    {
        $validated = $request->validate([
            'ay_id' => 'nullable|integer|exists:t_academic_years,id',
            'fp_name' => 'nullable|string|max:1000',
            'fp_recurring' => 'required|in:0,1',
            'fp_main_monthly_fee' => 'required|in:0,1',
            'fp_main_admission_fee' => 'required|in:0,1',
            'cg_id' => 'required|string|max:100',
        ]);

        try {
            // $feePlan = FeePlanModel::create($validated);
            $feePlan = FeePlanModel::create([
                'ay_id' => $validated['ay_id'] ?? null,
                'fp_name' => $validated['fp_name'] ?? null,
                'fp_recurring' => $validated['fp_recurring'],
                'fp_main_monthly_fee' => $validated['fp_main_monthly_fee'],
                'fp_main_admission_fee' => $validated['fp_main_admission_fee'],
                'cg_id' => $validated['cg_id'],
            ]);

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

    // csv import
    public function importCsv(Request $request)
    {
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/fee_plan.csv');
    
            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }
    
            // Truncate the table before import
            FeePlanModel::truncate();
    
            // Fetch the CSV content
            $csvContent = file_get_contents($csvFilePath);
    
            // Parse the CSV content using League\Csv
            $csv = Reader::createFromString($csvContent);
    
            // Set the header offset (first row as headers)
            $csv->setHeaderOffset(0);
    
            // Process the CSV records
            $records = (new Statement())->process($csv);
    
            foreach ($records as $row) {
                try {
                    // Validate and transform data
                    $fpName = $row['fp_name'] ?? null;
                    $fpRecurring = in_array($row['fp_recurring'], ['0', '1']) ? $row['fp_recurring'] : '1';
                    $fpMainMonthlyFee = in_array($row['fp_main_monthly_fee'], ['0', '1']) ? $row['fp_main_monthly_fee'] : '1';
                    $fpMainAdmissionFee = in_array($row['fp_main_admission_fee'], ['0', '1']) ? $row['fp_main_admission_fee'] : '0';
                    $cgId = $row['cg_id'] ?? null;
    
                    // Insert the record
                    FeePlanModel::create([
                        'id' => $row['fp_id'],
                        'ay_id' => $row['ay_id'] ?? null,
                        'fp_name' => $fpName,
                        'fp_recurring' => $fpRecurring,
                        'fp_main_monthly_fee' => $fpMainMonthlyFee,
                        'fp_main_admission_fee' => $fpMainAdmissionFee,
                        'cg_id' => $cgId,
                    ]);
                } catch (\Exception $e) {
                    // Log individual row errors
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }
    
            return response()->json([
                'message' => 'CSV imported successfully!',
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error('Failed to import CSV: ' . $e->getMessage());
    
            return response()->json([
                'message' => 'Failed to import CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
