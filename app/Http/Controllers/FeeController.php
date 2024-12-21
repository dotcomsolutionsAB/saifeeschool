<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeeModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

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
            'st_id' => 'nullable|integer|exists:t_students,id',
            'st_roll_no' => 'required|string|max:100',
            'fpp_id' => 'nullable|integer|exists:t_fee_plan_periods,id',
            'cg_id' => 'required|integer|exists:t_class_groups,id',
            'ay_id' => 'nullable|integer|exists:t_academic_years,id',
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

    // public function importCsv(Request $request)
    // {
    //     // Set the execution time to 5 minutes (300 seconds)
    //     ini_set('max_execution_time', 300);

    //     try {
    //         // Define the path to the CSV file
    //         $csvFilePath = storage_path('app/public/fee.csv');
    
    //         // Check if the file exists
    //         if (!file_exists($csvFilePath)) {
    //             return response()->json([
    //                 'message' => 'CSV file not found at the specified path.',
    //             ], 404);
    //         }
    
    //         // Truncate the table before import
    //         FeeModel::truncate();
    
    //         // Fetch the CSV content
    //         $csvContent = file_get_contents($csvFilePath);
    
    //         // Parse the CSV content using League\Csv
    //         $csv = Reader::createFromString($csvContent);
    
    //         // Set the header offset (first row as headers)
    //         $csv->setHeaderOffset(0);
    
    //         // Process the CSV records
    //         $records = (new Statement())->process($csv);

    //          // Define chunk size
    //         $chunkSize = 1000;

    //         // Initialize a counter
    //         $rows = iterator_to_array($records);
    //         $chunks = array_chunk($rows, $chunkSize);
        
    //         // foreach ($records as $row) {
    //             foreach ($chunks as $chunk) {
    //             try {
    //                 // Convert UNIX timestamps to MySQL DATE format
    //                 // $fppDueDate = (!empty($row['fpp_due_date']) && is_numeric($row['fpp_due_date']))
    //                 //     ? date('Y-m-d', $row['fpp_due_date'])
    //                 //     : null;
    
    //                 // $fPaidDate = (!empty($row['f_paid_date']) && is_numeric($row['f_paid_date']))
    //                 //     ? date('Y-m-d', $row['f_paid_date'])
    //                 //     : null;

    //                 // Validate and sanitize `f_late_fee_applicable`
    //                 $allowedLateFeeApplicableValues = ['0', '1'];
    //                 $fLateFeeApplicable = (isset($row['f_late_fee_applicable']) && in_array($row['f_late_fee_applicable'], $allowedLateFeeApplicableValues)) 
    //                     ? $row['f_late_fee_applicable'] 
    //                     : '0'; // Default to '0' if invalid or not provided

    //                 // Validate `f_paid_date`
    //                 $fPaidDate = (!empty($row['f_paid_date']) && strtolower($row['f_paid_date']) !== 'null' && is_numeric($row['f_paid_date']))
    //                 ? (int)$row['f_paid_date']
    //                 : null;

    
    //                 // Insert new data
    //                 FeeModel::create([
    //                     'st_id' => $row['st_id'] ?? null,
    //                     'st_roll_no' => $row['st_roll_no'] ?? null,
    //                     'fpp_id' => $row['fpp_id'] ?? null,
    //                     'cg_id' => $row['cg_id'] ?? null,
    //                     'ay_id' => $row['ay_id'] ?? null,
    //                     'fpp_name' => $row['fpp_name'] ?? null,
    //                     // 'fpp_due_date' => $fppDueDate,
    //                     'fpp_due_date' => $row['fpp_due_date'] ?? null,
    //                     'fpp_month_no' => (!empty($row['fpp_month_no']) && strtolower($row['fpp_month_no']) !== 'null') 
    //                         ? (int)$row['fpp_month_no'] 
    //                         : null,
    //                     'fpp_year_no' => (!empty($row['fpp_year_no']) && strtolower($row['fpp_year_no']) !== 'null') 
    //                         ? (int)$row['fpp_year_no'] 
    //                         : null,
    //                     'fpp_amount' => $row['fpp_amount'] ?? 0.0,
    //                     'f_concession' => $row['f_concession'] ?? 0.0,
    //                     'fpp_late_fee' => $row['fpp_late_fee'] ?? 0.0,
    //                     'f_late_fee_applicable' => $fLateFeeApplicable ,
    //                     'f_late_fee_paid' => $row['f_late_fee_paid'] ?? 0.0,
    //                     'f_total_paid' => $row['f_total_paid'] ?? 0.0,
    //                     'f_paid' => $row['f_paid'] ?? null,
    //                     'f_paid_date' => $fPaidDate,
    //                     // 'f_paid_date' => $row['f_paid_date'] ?? null,
    //                     'f_active' => $row['f_active'] ?? '1',
    //                     'fp_recurring' => $row['fp_recurring'] ?? '1',
    //                     'fp_main_monthly_fee' => $row['fp_main_monthly_fee'] ?? '1',
    //                     'fp_main_admission_fee' => $row['fp_main_admission_fee'] ?? '0',
    //                 ]);
    //             } catch (\Exception $e) {
    //                 // Log individual row errors
    //                 Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'CSV imported successfully!',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // Handle general exceptions
    //         Log::error('Failed to import CSV: ' . $e->getMessage());
    
    //         return response()->json([
    //             'message' => 'Failed to import CSV.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function importCsv(Request $request)
    // {
    //     // Set execution time and memory limits
    //     ini_set('max_execution_time', 300); // 5 minutes
    //     ini_set('memory_limit', '1024M');   // Increase memory limit

    //     try {
    //         // Path to the CSV file
    //         $csvFilePath = storage_path('app/public/fee.csv');

    //         if (!file_exists($csvFilePath)) {
    //             return response()->json([
    //                 'message' => 'CSV file not found at the specified path.',
    //             ], 404);
    //         }

    //         $csvContent = file_get_contents($csvFilePath);
    //         $csv = Reader::createFromString($csvContent);
    //         $csv->setHeaderOffset(0);
    //         $records = (new Statement())->process($csv);

    //         DB::transaction(function () use ($records) {
    //             foreach ($records as $row) {
    //                 try {
    //                     // Validate and process `fpp_due_date` and `f_paid_date` as Unix timestamps
    //                     $fppDueDate = is_numeric($row['fpp_due_date']) ? $row['fpp_due_date'] : null;
    //                     $fPaidDate = is_numeric($row['f_paid_date']) ? $row['f_paid_date'] : null;

    //                     // Validate `fpp_month_no` and `fpp_year_no` for numeric values or set null
    //                     $fppMonthNo = is_numeric($row['fpp_month_no']) ? $row['fpp_month_no'] : null;
    //                     $fppYearNo = is_numeric($row['fpp_year_no']) ? $row['fpp_year_no'] : null;

    //                     // Insert the data into the `t_fees` table
    //                     FeeModel::create([
    //                         'st_id' => $row['st_id'] ?? null,
    //                         'st_roll_no' => $row['st_roll_no'] ?? '',
    //                         'fpp_id' => $row['fpp_id'] ?? null,
    //                         'cg_id' => $row['cg_id'] ?? '',
    //                         'ay_id' => $row['ay_id'] ?? null,
    //                         'fpp_name' => $row['fpp_name'] ?? null,
    //                         'fpp_due_date' => $fppDueDate,
    //                         'fpp_month_no' => $fppMonthNo,
    //                         'fpp_year_no' => $fppYearNo,
    //                         'fpp_amount' => is_numeric($row['fpp_amount']) ? $row['fpp_amount'] : 0.00,
    //                         'f_concession' => is_numeric($row['f_concession']) ? $row['f_concession'] : 0.00,
    //                         'fpp_late_fee' => is_numeric($row['fpp_late_fee']) ? $row['fpp_late_fee'] : 0.00,
    //                         'f_late_fee_applicable' => in_array($row['f_late_fee_applicable'], ['0', '1']) ? $row['f_late_fee_applicable'] : '0',
    //                         'f_late_fee_paid' => is_numeric($row['f_late_fee_paid']) ? $row['f_late_fee_paid'] : 0.00,
    //                         'f_total_paid' => is_numeric($row['f_total_paid']) ? $row['f_total_paid'] : 0.00,
    //                         'f_paid' => in_array($row['f_paid'], ['0', '1']) ? $row['f_paid'] : '0',
    //                         'f_paid_date' => $fPaidDate,
    //                         'f_active' => in_array($row['f_active'], ['0', '1']) ? $row['f_active'] : '1',
    //                         'fp_recurring' => in_array($row['fp_recurring'], ['0', '1']) ? $row['fp_recurring'] : '1',
    //                         'fp_main_monthly_fee' => in_array($row['fp_main_monthly_fee'], ['0', '1']) ? $row['fp_main_monthly_fee'] : '1',
    //                         'fp_main_admission_fee' => in_array($row['fp_main_admission_fee'], ['0', '1']) ? $row['fp_main_admission_fee'] : '0',
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     // Log individual row errors for debugging
    //                     Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
    //                 }
    //             }
    //         });

    //         return response()->json([
    //             'message' => 'CSV imported successfully!',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error('Failed to import CSV: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Failed to import CSV.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            $csvFilePath = storage_path('app/public/fee.csv');

            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            $csvContent = file_get_contents($csvFilePath);
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0);
            $records = (new Statement())->process($csv);

            $batchSize = 1000; // Number of records to process in one batch
            $data = [];

            // Truncate the table before import
            FeeModel::truncate();

            DB::beginTransaction();
            foreach ($records as $index => $row) {
                try {
                    // Validate and prepare the data
                    $data[] = [
                        'id' => $row['f_id'],
                        'st_id' => $row['st_id'] ?? null,
                        'st_roll_no' => $row['st_roll_no'] ?? '',
                        'fpp_id' => $row['fpp_id'] ?? null,
                        'cg_id' => $row['cg_id'] ?? '',
                        'ay_id' => $row['ay_id'] ?? null,
                        'fpp_name' => $row['fpp_name'] ?? null,
                        'fpp_due_date' => is_numeric($row['fpp_due_date']) ? $row['fpp_due_date'] : null,
                        'fpp_month_no' => is_numeric($row['fpp_month_no']) ? $row['fpp_month_no'] : null,
                        'fpp_year_no' => is_numeric($row['fpp_year_no']) ? $row['fpp_year_no'] : null,
                        'fpp_amount' => is_numeric($row['fpp_amount']) ? $row['fpp_amount'] : 0.00,
                        'f_concession' => is_numeric($row['f_concession']) ? $row['f_concession'] : 0.00,
                        'fpp_late_fee' => is_numeric($row['fpp_late_fee']) ? $row['fpp_late_fee'] : 0.00,
                        'f_late_fee_applicable' => in_array($row['f_late_fee_applicable'], ['0', '1']) ? $row['f_late_fee_applicable'] : '0',
                        'f_late_fee_paid' => is_numeric($row['f_late_fee_paid']) ? $row['f_late_fee_paid'] : 0.00,
                        'f_total_paid' => is_numeric($row['f_total_paid']) ? $row['f_total_paid'] : 0.00,
                        'f_paid' => in_array($row['f_paid'], ['0', '1']) ? $row['f_paid'] : '0',
                        'f_paid_date' => is_numeric($row['f_paid_date']) ? $row['f_paid_date'] : null,
                        'f_active' => in_array($row['f_active'], ['0', '1']) ? $row['f_active'] : '1',
                        'fp_recurring' => in_array($row['fp_recurring'], ['0', '1']) ? $row['fp_recurring'] : '1',
                        'fp_main_monthly_fee' => in_array($row['fp_main_monthly_fee'], ['0', '1']) ? $row['fp_main_monthly_fee'] : '1',
                        'fp_main_admission_fee' => in_array($row['fp_main_admission_fee'], ['0', '1']) ? $row['fp_main_admission_fee'] : '0',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        FeeModel::insert($data);
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                FeeModel::insert($data);
            }

            DB::commit();

            return response()->json(['message' => 'CSV imported successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }
}
