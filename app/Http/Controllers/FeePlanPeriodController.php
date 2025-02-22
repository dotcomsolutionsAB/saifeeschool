<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeePlanPeriodModel;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\ClassGroupModel;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYearModel;
use App\Models\FeePlanModel;



class FeePlanPeriodController extends Controller
{
    //
     // Fetch all records or a specific record by ID
    //  public function index($id = null)
    //  {
    //      if ($id) {
    //          $feePlanParticular = FeePlanPeriodModel::find($id);
 
    //          if ($feePlanParticular) {
    //              return response()->json([
    //                  'message' => 'Fee plan particular fetched successfully!',
    //                  'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at'])
    //              ], 200);
    //          }
 
    //          return response()->json(['message' => 'Fee plan particular not found.'], 404);
    //      }
 
    //      $feePlanParticulars = FeePlanPeriodModel::all()->makeHidden(['created_at', 'updated_at']);
 
    //      return $feePlanParticulars->isNotEmpty()
    //          ? response()->json([
    //              'message' => 'Fee plan particulars fetched successfully!',
    //              'data' => $feePlanParticulars,
    //              'count' => $feePlanParticulars->count()
    //          ], 200)
    //          : response()->json(['message' => 'No fee plan particulars available.'], 400);
    //  }
    public function index(Request $request, $id = null)
    {
        try {
            if ($id) {
                // Fetch a specific Fee Plan Particular by ID, filtered by `ay_id` and `fp_id`
                $feePlanParticular = FeePlanPeriodModel::where('id', $id)
                    ->first();

                if ($feePlanParticular) {
                    return response()->json([
                        'message' => 'Fee plan particular fetched successfully!',
                        'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at']),
                    ], 200);
                }

                return response()->json(['message' => 'Fee plan particular not found.'], 404);
            }

            // Validate `ay_id` and `fp_id` as required when $id is not provided
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'fp_id' => 'required|integer|exists:t_fee_plans,id',
            ]);

            $ay_id = $validated['ay_id'];
            $fp_id = $validated['fp_id'];

            // Fetch all Fee Plan Particulars filtered by `ay_id` and `fp_id`
            $feePlanParticulars = FeePlanPeriodModel::where('ay_id', $ay_id)
                ->where('fp_id', $fp_id)
                ->get()
                ->makeHidden(['created_at', 'updated_at']);

            return $feePlanParticulars->isNotEmpty()
                ? response()->json([
                    'message' => 'Fee plan particulars fetched successfully!',
                    'data' => $feePlanParticulars,
                    'count' => $feePlanParticulars->count(),
                ], 200)
                : response()->json(['message' => 'No fee plan particulars available.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching fee plan particulars.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


 
     // Create a new record
     public function register(Request $request)
     {
         $validated = $request->validate([
             'fp_id' => 'required|integer|exists:t_fee_plans,id',
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
            //  $feePlanParticular = FeePlanPeriodModel::create($validated);
            $feePlanParticular = FeePlanPeriodModel::create([
                'fp_id' => $validated['fp_id'],
                'ay_id' => $validated['ay_id'],
                'fpp_name' => $validated['fpp_name'],
                'fpp_amount' => $validated['fpp_amount'],
                'fpp_late_fee' => $validated['fpp_late_fee'],
                'fpp_due_date' => $validated['fpp_due_date'],
                'fpp_month_no' => $validated['fpp_month_no'],
                'fpp_year_no' => $validated['fpp_year_no'],
                'fpp_order_no' => $validated['fpp_order_no'],
            ]);
 
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
         $feePlanParticular = FeePlanPeriodModel::find($id);
 
         if (!$feePlanParticular) {
             return response()->json(['message' => 'Fee plan particular not found.'], 404);
         }
 
         $validated = $request->validate([
             'fp_id' => 'sometimes|integer|exists:t_fee_plans,id',
             'ay_id' => 'sometimes|integer|exists:t_academic_years,id',
             'fpp_name' => 'sometimes|string|max:255',
             'fpp_amount' => 'sometimes|numeric|min:0',
             'fpp_late_fee' => 'sometimes|string|max:100',
             'fpp_due_date' => 'sometimes|date',
             'fpp_month_no' => 'sometimes|integer|min:1|max:12',
             'fpp_year_no' => 'sometimes|integer|min:2000',
             'fpp_order_no' => 'sometimes|string|max:100',
         ]);
 
         try {
            //  $feePlanParticular->update($validated);
            $feePlanParticular->update([
                'fp_id' => $validated['fp_id'] ?? $feePlanParticular->fp_id,
                'ay_id' => $validated['ay_id'] ?? $feePlanParticular->ay_id,
                'fpp_name' => $validated['fpp_name'] ?? $feePlanParticular->fpp_name,
                'fpp_amount' => $validated['fpp_amount'] ?? $feePlanParticular->fpp_amount,
                'fpp_late_fee' => $validated['fpp_late_fee'] ?? $feePlanParticular->fpp_late_fee,
                'fpp_due_date' => $validated['fpp_due_date'] ?? $feePlanParticular->fpp_due_date,
                'fpp_month_no' => $validated['fpp_month_no'] ?? $feePlanParticular->fpp_month_no,
                'fpp_year_no' => $validated['fpp_year_no'] ?? $feePlanParticular->fpp_year_no,
                'fpp_order_no' => $validated['fpp_order_no'] ?? $feePlanParticular->fpp_order_no,
            ]);
 
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
        $feePlanParticular = FeePlanPeriodModel::find($id);

        if (!$feePlanParticular) {
            return response()->json(['message' => 'Fee plan particular not found.'], 404);
        }

        try {
            $feePlanParticular->delete();

            return response()->json(['code'=>200,'message' => 'Fee plan particular deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code'=>500,
                'message' => 'Failed to delete fee plan particular.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // csv import
    public function importCsv(Request $request)
    {
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/fee_plan_period.csv');
    
            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }
    
            // Truncate the table before import
            FeePlanPeriodModel::truncate();
    
            // Fetch the CSV content
            $csvContent = file_get_contents($csvFilePath);
    
            // Parse the CSV content using League\Csv
            $csv = Reader::createFromString($csvContent);
    
            // Set the header offset (first row as headers)
            $csv->setHeaderOffset(0);
    
            // Process the CSV records
            $records = (new Statement())->process($csv);
    
            // Debug: Log the number of records
            Log::info('Number of records found: ' . iterator_count($records));
    
            foreach ($records as $row) {
                try {
                    // Log each row
                    Log::info('Processing row: ' . json_encode($row));
    
                    // Convert UNIX timestamp to MySQL DATE format
                    $fppDueDate = !empty($row['fpp_due_date']) ? date('Y-m-d', $row['fpp_due_date']) : null;

                    // Handle `NULL` or empty values
                    $fppMonthNo = (!empty($row['fpp_month_no']) && strtolower($row['fpp_month_no']) !== 'null') ? $row['fpp_month_no'] : null;
                    $fppYearNo = (!empty($row['fpp_year_no']) && strtolower($row['fpp_year_no']) !== 'null') ? $row['fpp_year_no'] : null;
                    $fppOrderNo = (!empty($row['fpp_order_no']) && strtolower($row['fpp_order_no']) !== 'null') ? $row['fpp_order_no'] : null;

                    // Insert the record
                    FeePlanPeriodModel::create([
                        'id' => $row['fpp_id'],
                        'fp_id' => $row['fp_id'] ?? null,
                        'ay_id' => $row['ay_id'] ?? null,
                        'fpp_name' => $row['fpp_name'] ?? null,
                        'fpp_amount' => $row['fpp_amount'] ?? 0.0,
                        'fpp_late_fee' => $row['fpp_late_fee'] ?? 0.0,
                        'fpp_due_date' => $fppDueDate,
                        'fpp_month_no' => $fppMonthNo,
                        'fpp_year_no' => $fppYearNo,
                        'fpp_order_no' => $fppOrderNo,
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
    public function getMonthlyFeePeriods(Request $request)
    {
        try {
            // Validate request inputs
            $validated = $request->validate([
                'fp_id' => 'required|integer|exists:t_fee_plans,id', // Fee Plan ID
                'search' => 'nullable|string|max:255', // Search by fee period name
                'offset' => 'nullable|integer|min:0',  // Pagination offset
                'limit' => 'nullable|integer|min:1|max:100', // Pagination limit (max 100)
            ]);
    
            $fp_id = $validated['fp_id'];
            $search = $validated['search'] ?? null;
            $offset = $validated['offset'] ?? 0;
            $limit = $validated['limit'] ?? 10;
    
            // Start the query
            $query = FeePlanPeriodModel::where('fp_id', $fp_id)
                ->orderBy('fpp_order_no', 'asc'); // Order by month sequence
    
            // Apply search filter (if provided)
            if (!empty($search)) {
                $query->where('fpp_name', 'LIKE', '%' . $search . '%');
            }
    
            // Get total count before pagination
            $totalCount = $query->count();
    
            // Apply pagination
            $feePeriods = $query
                ->offset($offset)
                ->limit($limit)
                ->get();
    
            if ($feePeriods->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'No fee plan periods found for the given Fee Plan ID.',
                ], 404);
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Monthly fee periods fetched successfully!',
                'data' => $feePeriods,
                'count' => $totalCount, // Total records before pagination
                'offset' => $offset,
                'limit' => $limit,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching monthly fee periods.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function createOrUpdateMonthlyFeePeriods(Request $request)
    {
        try {
            // ✅ Validate request data
            $validated = $request->validate([
                'fp_id' => 'required|integer|exists:t_fee_plans,id',
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'fees' => 'required|array|min:1',
                'fees.*.id' => 'nullable|integer|exists:t_fee_plan_periods,id',
                'fees.*.amount' => 'nullable|numeric|min:0',
                'fees.*.due_date' => 'required|date',
            ]);
    
            $fp_id = $validated['fp_id'];
            $ay_id = $validated['ay_id'];
            $fees = $validated['fees'];
    
            // ✅ Get academic year details
            $academicYear = AcademicYearModel::find($ay_id);
            if (!$academicYear) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Academic year not found.',
                ], 404);
            }
    
            // ✅ Fetch Fee Plan
            $feePlan = FeePlanModel::where('id', $fp_id)->first();
            if (!$feePlan) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Fee plan not found.',
                ], 404);
            }
    
            // ✅ Prevent modification of `ay_id`
            if ($feePlan->ay_id != $ay_id) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Modification of the academic year is prohibited once the fee plan is created.',
                ], 400);
            }
    
            // ✅ Fetch the latest `fpp_late_fee`
            $lateFee = FeePlanPeriodModel::where('fp_id', $fp_id)
                ->where('ay_id', $ay_id)
                ->orderBy('id', 'desc')
                ->value('fpp_late_fee') ?? 0;
    
            $className = $feePlan->fp_name;
    
            // ✅ Order Mapping for Month Number → Order Number
            $orderMapping = [
                4 => 1, 5 => 2, 6 => 3, 7 => 4, 8 => 5, 9 => 6,
                10 => 7, 11 => 8, 12 => 9, 1 => 10, 2 => 11, 3 => 12
            ];
    
            // ✅ Ensure fees are sorted by `due_date`
            usort($fees, function ($a, $b) {
                return strtotime($a['due_date']) - strtotime($b['due_date']);
            });
    
            $warnings = [];
    
            foreach ($fees as $fee) {
                $feePeriodId = $fee['id'] ?? null;
                $dueDate = $fee['due_date'];
                $month_no = date('n', strtotime($dueDate)); // Extract month number
                $year_no = date('Y', strtotime($dueDate)); // Extract year
                $order_no = $orderMapping[$month_no] ?? null;
    
                if ($order_no === null) {
                    return response()->json([
                        'code' => 400,
                        'status' => false,
                        'message' => 'Invalid month in due date.',
                    ], 400);
                }
    
                // ✅ Generate Name Format: "Class X (June 2024)"
                $monthName = date('F', strtotime($dueDate));
                $fpp_name = "{$className} ({$monthName} {$year_no})";
    
                // ✅ Check for existing entry for the same month & year
                $existingFeePeriod = FeePlanPeriodModel::where([
                    'fp_id' => $fp_id,
                    'ay_id' => $ay_id,
                    'fpp_month_no' => $month_no,
                    'fpp_year_no' => $year_no
                ])->first();
    
                if ($feePeriodId) {
                    // ✅ If `id` is provided, update existing fee period
                    $feePeriod = FeePlanPeriodModel::find($feePeriodId);
                    if (!$feePeriod) {
                        return response()->json([
                            'code' => 404,
                            'status' => false,
                            'message' => "Fee period with ID {$feePeriodId} not found.",
                        ], 404);
                    }
    
                    $feePeriod->update([
                        'fpp_amount' => $fee['amount'],
                        'fpp_due_date' => $dueDate,
                        'fpp_late_fee' => $lateFee,
                        'fpp_order_no' => $order_no,
                    ]);
                } else {
                    // ✅ If creating a new entry, check if duplicate exists
                    if ($existingFeePeriod) {
                        $warnings[] = "Warning: Fee period for {$monthName} {$year_no} already exists.";
                        continue; // Skip duplicate entry
                    }
    
                    // ✅ Create new entry
                    FeePlanPeriodModel::create([
                        'fp_id' => $fp_id,
                        'ay_id' => $ay_id,
                        'fpp_name' => $fpp_name,
                        'fpp_amount' => $fee['amount'],
                        'fpp_due_date' => $dueDate,
                        'fpp_late_fee' => $lateFee,
                        'fpp_month_no' => $month_no,
                        'fpp_year_no' => $year_no,
                        'fpp_order_no' => $order_no,
                    ]);
                }
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Monthly fee plan periods created or updated successfully!',
                'warnings' => $warnings,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while creating/updating monthly fee periods.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
