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
    
            // Optimized query using LEFT JOIN to fetch fee plans with their latest fee period
            $feePlans = DB::table('t_fee_plans as fp')
                ->leftJoin('t_fee_plan_periods as fpp', function ($join) use ($ay_id) {
                    $join->on('fp.id', '=', 'fpp.fp_id')
                        ->where('fpp.ay_id', '=', $ay_id);
                })
                ->leftJoin(DB::raw('(SELECT fpp.fp_id, MAX(fpp.fpp_due_date) as max_due_date FROM t_fee_plan_periods as fpp WHERE fpp.ay_id = ' . $ay_id . ' GROUP BY fpp.fp_id) as latest_fpp'), function ($join) {
                    $join->on('fpp.fp_id', '=', 'latest_fpp.fp_id')
                        ->on('fpp.fpp_due_date', '=', 'latest_fpp.max_due_date');
                })
                ->leftJoin(DB::raw('(SELECT fpp.fp_id, COUNT(DISTINCT f.st_id) as student_count FROM t_fees as f INNER JOIN t_fee_plan_periods as fpp ON f.fpp_id = fpp.id WHERE fpp.ay_id = ' . $ay_id . ' GROUP BY fpp.fp_id) as student_counts'), function ($join) {
                    $join->on('fp.id', '=', 'student_counts.fp_id');
                })
                ->selectRaw("
                    fp.id as fp_id,
                    fp.fp_name,
                    fp.fp_recurring,
                    fp.fp_main_monthly_fee,
                    fp.fp_main_admission_fee,
                    fpp.fpp_due_date as last_due_date,
                    fpp.fpp_amount as last_fpp_amount,
                    fpp.fpp_order_no as last_fpp_order_no,
                    COALESCE(student_counts.student_count, 0) as applied_students
                ")
                ->where('fp.ay_id', $ay_id)
                ->when(!empty($validated['type']), function ($query) use ($validated) {
                    switch ($validated['type']) {
                        case 'monthly':
                            $query->where('fp.fp_recurring', '1')->where('fp.fp_main_monthly_fee', '1');
                            break;
                        case 'admission':
                            $query->where('fp.fp_main_admission_fee', '1');
                            break;
                        case 'one_time':
                            $query->where('fp.fp_recurring', '0')->where('fp.fp_main_monthly_fee', '1');
                            break;
                        case 'recurring':
                            $query->where('fp.fp_recurring', '1')->where('fp.fp_main_monthly_fee', '0');
                            break;
                    }
                })
                ->groupBy('fp.id', 'fp.fp_name', 'fp.fp_recurring', 'fp.fp_main_monthly_fee', 'fp.fp_main_admission_fee', 'fpp.fpp_due_date', 'fpp.fpp_amount', 'fpp.fpp_order_no', 'student_counts.student_count')
                ->orderBy('fpp.fpp_order_no', 'desc')
                ->get();
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Fee plans with periods fetched successfully!',
                'data' => $feePlans,
                'count' => $feePlans->count(),
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
    public function createOrUpdate(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'fp_id' => 'nullable|integer|exists:t_fee_plans,id', // Required only for update
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'fp_name' => 'required|string|max:1000',
                'type' => 'required|in:monthly,one_time,admission,recurring', // Type constraint
                'amount' => 'nullable|numeric|min:0', // Required for non-monthly types
                'due_date' => 'nullable|date', // Required for non-monthly types
                'late_fee' => 'nullable|numeric|min:0', // Late fee allowed for all types
            ]);
    
            // Check for duplicate fee name within the academic year
            $existingFeePlan = FeePlanModel::where('ay_id', $validated['ay_id'])
                ->where('fp_name', $validated['fp_name'])
                ->when(!empty($validated['fp_id']), function ($query) use ($validated) {
                    return $query->where('id', '!=', $validated['fp_id']);
                })
                ->exists();
    
            if ($existingFeePlan) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'A fee plan with this name already exists in the given academic year.',
                ], 400);
            }
    
            // Determine flags based on the type
            switch ($validated['type']) {
                case 'monthly':
                    $fp_recurring = '1';
                    $fp_main_monthly_fee = '1';
                    $fp_main_admission_fee = '0';
                    break;
                case 'admission':
                    $fp_recurring = '0';
                    $fp_main_monthly_fee = '0';
                    $fp_main_admission_fee = '1';
                    break;
                case 'one_time':
                    $fp_recurring = '0';
                    $fp_main_monthly_fee = '1';
                    $fp_main_admission_fee = '0';
                    break;
                case 'recurring':
                    $fp_recurring = '1';
                    $fp_main_monthly_fee = '0';
                    $fp_main_admission_fee = '0';
                    break;
                default:
                    return response()->json([
                        'code' => 400,
                        'status' => false,
                        'message' => 'Invalid fee type provided.'
                    ], 400);
            }
    
            // If `fp_id` exists, update the fee plan
            if (!empty($validated['fp_id'])) {
                $feePlan = FeePlanModel::find($validated['fp_id']);
                $feePlan->update([
                    'ay_id' => $validated['ay_id'],
                    'fp_name' => $validated['fp_name'],
                    'fp_recurring' => $fp_recurring,
                    'fp_main_monthly_fee' => $fp_main_monthly_fee,
                    'fp_main_admission_fee' => $fp_main_admission_fee,
                ]);
    
                $message = 'Fee plan updated successfully!';
            } else {
                // Create a new fee plan
                $feePlan = FeePlanModel::create([
                    'ay_id' => $validated['ay_id'],
                    'fp_name' => $validated['fp_name'],
                    'fp_recurring' => $fp_recurring,
                    'fp_main_monthly_fee' => $fp_main_monthly_fee,
                    'fp_main_admission_fee' => $fp_main_admission_fee,
                ]);
    
                $message = 'Fee plan created successfully!';
            }
    
            // Now, handle fee plan period entries in `t_fee_plan_periods`
            if ($validated['type'] === 'monthly') {
                // Create an entry for monthly plans with name "Name (Original Year)"
                FeePlanPeriodModel::updateOrCreate(
                    [
                        'fp_id' => $feePlan->id,
                        'ay_id' => $validated['ay_id']
                    ],
                    [
                        'fpp_name' => "{$validated['fp_name']} (Original {$validated['ay_id']})",
                        'fpp_amount' => null, // Monthly fees don't have an amount
                        'fpp_due_date' => null, // No due date for monthly fees
                        'fpp_late_fee' => $validated['late_fee'] ?? 0, // Late fee can be set
                        'fpp_month_no' => null,
                        'fpp_year_no' => null,
                        'fpp_order_no' => null
                    ]
                );
            } else {
                // Create or update fee plan period for other types
                FeePlanPeriodModel::updateOrCreate(
                    [
                        'fp_id' => $feePlan->id,
                        'ay_id' => $validated['ay_id']
                    ],
                    [
                        'fpp_name' => $validated['fp_name'],
                        'fpp_amount' => $validated['amount'] ?? 0,
                        'fpp_due_date' => $validated['due_date'] ?? null,
                        'fpp_late_fee' => $validated['late_fee'] ?? 0,
                        'fpp_month_no' => null,
                        'fpp_year_no' => null,
                        'fpp_order_no' => null
                    ]
                );
            }
    
            return response()->json([
                'code' => 201,
                'status' => true,
                'message' => $message,
                'data' => $feePlan->makeHidden(['created_at', 'updated_at']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to create or update fee plan.',
                'error' => $e->getMessage(),
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
