<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeePlanPeriodModel;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

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
        // Validate `ay_id` and `fp_id` as required
        $validated = $request->validate([
            'ay_id' => 'required|integer|exists:t_academic_years,id',
            'fp_id' => 'required|integer|exists:t_fee_plans,id',
        ]);

        $ay_id = $validated['ay_id'];
        $fp_id = $validated['fp_id'];

        if ($id) {
            // Fetch a specific Fee Plan Particular by ID, filtered by `ay_id` and `fp_id`
            $feePlanParticular = FeePlanPeriodModel::where('id', $id)
                ->where('ay_id', $ay_id)
                ->where('fp_id', $fp_id)
                ->first();

            if ($feePlanParticular) {
                return response()->json([
                    'message' => 'Fee plan particular fetched successfully!',
                    'data' => $feePlanParticular->makeHidden(['created_at', 'updated_at']),
                ], 200);
            }

            return response()->json(['message' => 'Fee plan particular not found.'], 404);
        }

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

            return response()->json(['message' => 'Fee plan particular deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
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
}
