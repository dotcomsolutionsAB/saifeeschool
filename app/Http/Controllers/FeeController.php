<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeeModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\ClassGroupModel;

use App\Models\StudentModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use NumberFormatter;
use Carbon\Carbon;
use App\Exports\FeesExport;
use Maatwebsite\Excel\Facades\Excel;


class FeeController extends Controller
{
    //
    // Fetch all records or a specific record by ID
    // public function index($id = null)
    // {
    //     if ($id) {
    //         $studentFee = FeeModel::find($id);
    //         if ($studentFee) {
    //             return response()->json([
    //                 'message' => 'Student fee record fetched successfully!',
    //                 'data' => $studentFee->makeHidden(['created_at', 'updated_at'])
    //             ], 200);
    //         }

    //         return response()->json(['message' => 'Student fee record not found.'], 404);
    //     }

    //     else
    //     {
    //         $studentFees = FeeModel::all()->makeHidden(['created_at', 'updated_at']);

    //         return $studentFees->isNotEmpty()
    //             ? response()->json([
    //                 'message' => 'Student fee records fetched successfully!',
    //                 'data' => $studentFees,
    //                 'count' => $studentFees->count()
    //             ], 200)
    //             : response()->json(['message' => 'No student fee records available.'], 400);
            
    //     }
    // }
    // public function index(Request $request, $id = null)
    // {
    //     // Validate `ay_id` as required
    //     $validated = $request->validate([
    //         'ay_id' => 'required|integer|exists:t_academic_years,id',
    //         'status' => 'required|in:paid, unpaid',
    //         'st_id' => 'required|exists:exists:t_academic_years,id,id'
    //     ]);

    //     $ay_id = $validated['ay_id'];

    //     if ($id) {
    //         $studentFee = FeeModel::where('id', $id)->where('ay_id', $ay_id)->first();

    //         if ($studentFee) {
    //             return response()->json([
    //                 'message' => 'Student fee record fetched successfully!',
    //                 'data' => $studentFee->makeHidden(['created_at', 'updated_at']),
    //             ], 200);
    //         }

    //         return response()->json(['message' => 'Student fee record not found.'], 404);
    //     } else {
    //         $studentFees = FeeModel::where('ay_id', $ay_id)->get()->makeHidden(['created_at', 'updated_at']);

    //         return $studentFees->isNotEmpty()
    //             ? response()->json([
    //                 'message' => 'Student fee records fetched successfully!',
    //                 'data' => $studentFees,
    //                 'count' => $studentFees->count(),
    //             ], 200)
    //             : response()->json(['message' => 'No student fee records available.'], 404);
    //     }
    // }

    public function index(Request $request, $id = null)
    {
        if ($id) {
            // Fetch a specific fee record by ID, without requiring other validations
            $studentFee = FeeModel::find($id);

            if ($studentFee) {
                return response()->json([
                    'message' => 'Student fee record fetched successfully!',
                    'data' => $studentFee->makeHidden(['created_at', 'updated_at']),
                ], 200);
            }

            return response()->json(['message' => 'Student fee record not found.'], 404);
        } else {
            // Validate request inputs only for fetching multiple records
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'status' => 'required|in:paid,unpaid',
                'st_id' => 'required|integer|exists:t_students,id',
            ]);

            $ay_id = $validated['ay_id'];
            $st_id = $validated['st_id'];
            $f_paid = $validated['status'] === 'paid' ? 1 : 0;

            // Fetch all fee records filtered by `ay_id`, `st_id`, and `status`
            $studentFees = FeeModel::where('ay_id', $ay_id)
                ->where('st_id', $st_id)
                ->where('f_paid', $f_paid)
                ->get()
                ->makeHidden(['created_at', 'updated_at']);

            return $studentFees->isNotEmpty()
                ? response()->json([
                    'message' => 'Student fee records fetched successfully!',
                    'data' => array_slice($studentFees->toArray(), 0, 10),
                ], 200)
                : response()->json(['message' => 'No student fee records available.'], 404);
        }
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
            // $studentFee = FeeModel::create($validated);
            $studentFee = FeeModel::create([
                'st_id' => $validated['st_id'] ?? null,
                'st_roll_no' => $validated['st_roll_no'],
                'fpp_id' => $validated['fpp_id'] ?? null,
                'cg_id' => $validated['cg_id'],
                'ay_id' => $validated['ay_id'] ?? null,
                'fpp_name' => $validated['fpp_name'] ?? null,
                'fpp_due_date' => $validated['fpp_due_date'] ?? null,
                'fpp_month_no' => $validated['fpp_month_no'] ?? null,
                'fpp_year_no' => $validated['fpp_year_no'] ?? null,
                'fpp_amount' => $validated['fpp_amount'],
                'f_concession' => $validated['f_concession'] ?? 0,
                'fpp_late_fee' => $validated['fpp_late_fee'] ?? 0,
                'f_late_fee_applicable' => $validated['f_late_fee_applicable'] ?? '0',
                'f_late_fee_paid' => $validated['f_late_fee_paid'] ?? 0,
                'f_total_paid' => $validated['f_total_paid'] ?? 0,
                'f_paid' => $validated['f_paid'] ?? '0',
                'f_paid_date' => $validated['f_paid_date'] ?? null,
                'f_active' => $validated['f_active'] ?? '1',
                'fp_recurring' => $validated['fp_recurring'] ?? '1',
                'fp_main_monthly_fee' => $validated['fp_main_monthly_fee'] ?? '1',
                'fp_main_admission_fee' => $validated['fp_main_admission_fee'] ?? '0',
            ]);

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
            // $studentFee->update($validated);
            $studentFee->update([
                'st_id' => $validated['st_id'] ?? $studentFee->st_id,
                'st_roll_no' => $validated['st_roll_no'] ?? $studentFee->st_roll_no,
                'fpp_id' => $validated['fpp_id'] ?? $studentFee->fpp_id,
                'cg_id' => $validated['cg_id'] ?? $studentFee->cg_id,
                'ay_id' => $validated['ay_id'] ?? $studentFee->ay_id,
                'fpp_name' => $validated['fpp_name'] ?? $studentFee->fpp_name,
                'fpp_due_date' => $validated['fpp_due_date'] ?? $studentFee->fpp_due_date,
                'fpp_month_no' => $validated['fpp_month_no'] ?? $studentFee->fpp_month_no,
                'fpp_year_no' => $validated['fpp_year_no'] ?? $studentFee->fpp_year_no,
                'fpp_amount' => $validated['fpp_amount'] ?? $studentFee->fpp_amount,
                'f_concession' => $validated['f_concession'] ?? $studentFee->f_concession,
                'fpp_late_fee' => $validated['fpp_late_fee'] ?? $studentFee->fpp_late_fee,
                'f_late_fee_applicable' => $validated['f_late_fee_applicable'] ?? $studentFee->f_late_fee_applicable,
                'f_late_fee_paid' => $validated['f_late_fee_paid'] ?? $studentFee->f_late_fee_paid,
                'f_total_paid' => $validated['f_total_paid'] ?? $studentFee->f_total_paid,
                'f_paid' => $validated['f_paid'] ?? $studentFee->f_paid,
                'f_paid_date' => $validated['f_paid_date'] ?? $studentFee->f_paid_date,
                'f_active' => $validated['f_active'] ?? $studentFee->f_active,
                'fp_recurring' => $validated['fp_recurring'] ?? $studentFee->fp_recurring,
                'fp_main_monthly_fee' => $validated['fp_main_monthly_fee'] ?? $studentFee->fp_main_monthly_fee,
                'fp_main_admission_fee' => $validated['fp_main_admission_fee'] ?? $studentFee->fp_main_admission_fee,
            ]);

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

            return response()->json(['code'=>200,'message' => 'Student fee record deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code'=>500,
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

    public function generatePendingFeesPDF(Request $request)
{
    $validated = $request->validate([
        'year' => 'required|integer',
        'class_name' => 'required|string', // Expect a comma-separated list of class IDs
        'fee_status' => 'nullable|integer|in:0,1',
    ]);

    $ay_id = $validated['year'];
    $cg_ids = explode(',', $validated['class_name']); // Convert comma-separated IDs into an array
    $fee_status = $validated['fee_status'] ?? 0; // Default: unpaid fees
    $feePaid = $fee_status == 1;

    // DB::enableQueryLog(); // Enable query logging

    // Fetch Class Groups with fees and students
    // $classGroups = ClassGroupModel::with(['fees' => function ($query) use ($ay_id, $feePaid) {
    //     $query->where('ay_id', $ay_id)
    //           ->where('f_active', 1)
    //           ->when(!$feePaid, function ($q) {
    //               $q->where('f_paid', 0);
    //           })
    //           ->with('student');
    // }])->whereIn('id', $cg_ids)->get();

    $classGroups = ClassGroupModel::with(['fees' => function ($query) use ($ay_id, $feePaid) {
        $query->where('ay_id', $ay_id)
              ->whereIn('f_active', ['1', 1]) // Handle both string and integer for f_active
              ->when(!$feePaid, function ($q) {
                  $q->whereIn('f_paid', ['0', 0]); // Unpaid fees
              }, function ($q) {
                  $q->whereIn('f_paid', ['1', 1]); // Paid fees
              })
            //   ->groupBy('st_id') // Group by student ID
              ->with('student');
    }])
    ->whereIn('id', $cg_ids) // Filter by class group IDs
    ->get();

    // dd(DB::getQueryLog());

    if ($classGroups->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No records found for the provided class group IDs or academic year.',
        ], 404);
    }

    // Prepare data for Blade view
    $classData = [];
    foreach ($classGroups as $classGroup) {
        $students = [];
        $classTotal = 0;

        // Group fees by student
        $groupedFees = $classGroup->fees->groupBy('st_id');

        foreach ($groupedFees as $studentId => $fees) {
            $studentName = $fees->first()->student->st_first_name . ' ' . $fees->first()->student->st_last_name;
            $rollNo = $fees->first()->student->st_roll_no;
            $studentTotal = 0;
            $feeDetails = [];

            foreach ($fees as $fee) {
                $feeAmount = $fee->fpp_amount - $fee->f_concession;
                $lateFee = $fee->fpp_late_fee ?? 0;
                $totalAmount = $feeAmount + $lateFee;

                $studentTotal += $totalAmount;

                $feeDetails[] = [
                    'fee_id' => $fee->id,
                    'fee_name' => $fee->fpp_name,
                    'total' => $totalAmount,
                ];
            }

            $students[] = [
                'name' => $studentName,
                'roll_no' => $rollNo,
                'fees' => $feeDetails,
                'student_total' => $studentTotal,
            ];

            // print_r($students);

            $classTotal += $studentTotal;
        }

        $classData[] = [
            'class_name' => $classGroup->cg_name,
            'students' => $students,
            'class_total' => $classTotal,
        ];
    }

    // Render Blade view
    $html = view('pending_fees', ['classes' => $classData])->render();

    // Generate PDF using MPDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);

    // Output PDF inline
    return $mpdf->Output('Pending_Fees.pdf', 'I');
}

//     public function generatePendingFeesPDF(Request $request)
// {
//     // Validate Inputs
//     $validated = $request->validate([
//         'year' => 'required|integer',        // Academic Year ID
//         'class_name' => 'required|integer', // Class Group ID
//         'fee_status' => 'nullable|integer|in:0,1', // Fee status (0 = unpaid, 1 = paid)
//     ]);

//     $ay_id = $validated['year'];
//     $cg_id = $validated['class_name'];
//     $fee_status = $validated['fee_status'] ?? 0; // Default: unpaid fees

//     // Fee status filter
//     $feePaid = $fee_status == 1;

//     // Fetch Class Group with Fees and Students
//     $classGroup = ClassGroupModel::with(['fees' => function ($query) use ($ay_id, $feePaid) {
//         $query->where('ay_id', $ay_id)
//               ->where('f_active', 1)
//               ->when(!$feePaid, function ($q) {
//                   $q->where('f_paid', 0);
//               })
//               ->with('student');
//     }])->where('id', $cg_id)->first();

//     if (!$classGroup) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Invalid Class Group or Academic Year ID',
//         ], 400);
//     }

//     // Prepare data for Blade view
//     $classData = [];
//     $students = [];
//     $classTotal = 0;

//     foreach ($classGroup->fees as $fee) {
//         $feeAmount = $fee->fpp_amount - $fee->f_concession;
//         $lateFee = $fee->fpp_late_fee ?? 0;
//         $totalAmount = $feeAmount + $lateFee;

//         $classTotal += $totalAmount;

//         $students[] = [
//             'name' => $fee->student->st_first_name . ' ' . $fee->student->st_last_name,
//             'roll_no' => $fee->student->st_roll_no,
//             'fee_name' => $fee->fpp_name,
//             'total' => $totalAmount,
//         ];
//     }

//     $classData[] = [
//         'class_name' => $classGroup->cg_name,
//         'students' => $students,
//         'class_total' => $classTotal,
//     ];

//     // Load Blade view and render HTML
//     $html = view('pending_fees', ['classes' => $classData])->render();

//     // Generate PDF using MPDF
//     $mpdf = new \Mpdf\Mpdf();
//     $mpdf->WriteHTML($html);

//     // Output PDF
//     return $mpdf->Output('Pending_Fees.pdf', 'I'); // Inline view
// }

    // public function generatePendingFeesPDF(Request $request)
    // {
    //     // Validate Inputs
    //     $validated = $request->validate([
    //         'year' => 'required|integer',        // Academic Year ID
    //         'class_name' => 'required|integer', // Class Group ID
    //         'fee_status' => 'nullable|integer|in:0,1', // Fee status (0 = unpaid, 1 = paid)
    //     ]);

    //     $ay_id = $validated['year'];
    //     $cg_id = $validated['class_name'];
    //     $fee_status = $validated['fee_status'] ?? 0; // Default: unpaid fees

    //     // Fee status filter
    //     $feePaid = $fee_status == 1;

    //     // Ensure the class group belongs to the correct academic year
    //     $classGroup = ClassGroupModel::where('id', $cg_id)
    //         ->where('ay_id', $ay_id)
    //         ->first();

    //     if (!$classGroup) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Class group not found or does not belong to the provided academic year',
    //         ], 404);
    //     }
    //     // DB::enableQueryLog(); // Enable query logging
    //     // Fetch fees with student data
    //     $fees = FeeModel::where('cg_id', $cg_id)
    //         ->where('ay_id', $ay_id)
    //         ->where('f_active', 1)
    //         ->when(!$feePaid, function ($query) {
    //             $query->where('f_paid', 0);
    //         })
    //         ->with('student')
    //         ->get();
    //         // Get and dump query logs
    //   // dd(DB::getQueryLog());

    //     if ($fees->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No fees records found for the specified class group and academic year',
    //         ], 404);
    //     }

    //     // Prepare data for Blade view
    //     $students = [];
    //     $classTotal = 0;

    //     foreach ($fees as $fee) {
    //         $feeAmount = $fee->fpp_amount - $fee->f_concession;
    //         $lateFee = $fee->fpp_late_fee ?? 0;
    //         $totalAmount = $feeAmount + $lateFee;

    //         $classTotal += $totalAmount;

    //         $students[] = [
    //             'name' => $fee->student->st_first_name . ' ' . $fee->student->st_last_name,
    //             'roll_no' => $fee->student->st_roll_no,
    //             'fee_name' => $fee->fpp_name,
    //             'total' => $totalAmount,
    //         ];
    //     }

    //     $classData = [
    //         'class_name' => $classGroup->cg_name,
    //         'students' => $students,
    //         'class_total' => $classTotal,
    //     ];

    //     // Load Blade view and render HTML
    //     $html = view('pending_fees', ['classData' => $classData])->render();

    //     // Generate PDF using MPDF
    //     $mpdf = new \Mpdf\Mpdf();
    //     $mpdf->WriteHTML($html);

    //     // Output PDF
    //     return $mpdf->Output('Pending_Fees.pdf', 'I'); // Inline view
    // }

    // public function generatePendingFeesPDF(Request $request)
    // {
    //     // Fetch data from database
    //     $ay_id = $request->input('year');
    //     $cg_id = $request->input('class_name');
    //     $filter_fee_status = $request->input('fee_status') == 0 ? "AND f_paid = 0" : "AND f_paid = 1";

    //     $classes = [];
    //     $query = "SELECT * FROM t_class_groups WHERE ay_id = ?";
    //     $result = \DB::select($query, [$ay_id]);

    //     foreach ($result as $class) {
    //         $students = \DB::select("
    //             SELECT
    //                 s.st_first_name,
    //                 s.st_last_name,
    //                 s.st_roll_no,
    //                 f.fpp_name,
    //                 f.fpp_amount,
    //                 f.f_concession,
    //                 f.fpp_late_fee
    //             FROM fee f
    //             JOIN t_students s ON s.st_id = f.st_id
    //             WHERE f.cg_id = ? $filter_fee_status
    //             ", [$class->cg_id]);

    //         $classTotal = 0;
    //         $studentData = [];

    //         foreach ($students as $student) {
    //             $feeAmount = $student->fpp_amount - $student->f_concession;
    //             $lateFee = $student->fpp_late_fee;
    //             $total = $feeAmount + $lateFee;

    //             $classTotal += $total;

    //             $studentData[] = [
    //                 'name' => $student->st_first_name . ' ' . $student->st_last_name,
    //                 'roll_no' => $student->st_roll_no,
    //                 'fee_name' => $student->fpp_name,
    //                 'total' => $total,
    //             ];
    //         }

    //         $classes[] = [
    //             'class_name' => $class->cg_name,
    //             'students' => $studentData,
    //             'class_total' => $classTotal,
    //         ];
    //     }

    //     // Load the Blade view with data
    //     $html = view('pending_fees', compact('classes'))->render();

    //     // Generate PDF using MPDF
    //     $mpdf = new Mpdf();
    //     $mpdf->WriteHTML($html);

    //     // Output PDF
    //     return $mpdf->Output('Pending_Fees.pdf', 'I'); // 'I' for inline view, 'D' for download
    // }
    public function index_all(Request $request)
{
    try {
        // Validate request input
        $validated = $request->validate([
            'search'    => 'nullable|string|max:255', // Search by Roll No or Name
            'cg_id'     => 'nullable|string', // Class ID (comma-separated)
            'status'    => 'nullable|in:paid,unpaid', // Payment Status filter
            'year'      => 'nullable|integer', // Academic Year filter
            'date_from' => 'nullable|date', // Start date filter
            'date_to'   => 'nullable|date|after_or_equal:date_from', // End date filter
            'type'      => 'nullable|in:monthly,admission,one_time,recurring', // Fee Type filter
            'offset'    => 'nullable|integer|min:0', // Pagination offset
            'limit'     => 'nullable|integer|min:1|max:100', // Limit (default: 10, max: 100)
        ]);

        // Set pagination defaults
        $offset = $validated['offset'] ?? 0;
        $limit = $validated['limit'] ?? 10;

        // **Start Query from `t_fees` Table**
        $query = DB::table('t_fees as fees')
            ->join('t_students as stu', 'fees.st_id', '=', 'stu.id')
            ->leftJoin('t_class_groups as cg', 'fees.cg_id', '=', 'cg.id')
            ->selectRaw("
                stu.st_roll_no,
                CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
                fees.fpp_name AS fee_name,
                fees.fpp_amount AS base_amount,
                fees.fpp_due_date AS due_date,
                IF(fees.f_late_fee_applicable = '1', fees.fpp_late_fee, '0') AS late_fee,
                fees.f_concession AS concession,
                (fees.fpp_amount + IF(fees.f_late_fee_applicable = '1', fees.fpp_late_fee, 0) - IFNULL(fees.f_concession, 0)) AS total_amount,
                IF(fees.f_paid = '1', '1', '0') AS payment_status
            ")
            ->orderBy('fees.fpp_due_date', 'asc');

        // **Search Filter (Roll No or Name)**
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('stu.st_roll_no', 'LIKE', $searchTerm)
                  ->orWhere('stu.st_first_name', 'LIKE', $searchTerm)
                  ->orWhere('stu.st_last_name', 'LIKE', $searchTerm);
            });
        }

        // **Filter by Class ID**
        if (!empty($validated['cg_id'])) {
            $cgIds = explode(',', $validated['cg_id']);
            $query->whereIn('fees.cg_id', $cgIds);
        }

        // **Filter by Payment Status**
        if (!empty($validated['status'])) {
            $query->where('fees.f_paid', $validated['status'] === 'paid' ? '1' : '0');
        }

        // **Filter by Academic Year**
        if (!empty($validated['year'])) {
            $query->where('fees.fpp_year_no', $validated['year']);
        }

        // **Filter by Date Range**
        if (!empty($validated['date_from'])) {
            $query->whereDate('fees.fpp_due_date', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->whereDate('fees.fpp_due_date', '<=', $validated['date_to']);
        }

        // **Filter by Fee Type**
        if (!empty($validated['type'])) {
            switch ($validated['type']) {
                case 'monthly':
                    $query->where('fees.fp_recurring', '1')->where('fees.fp_main_monthly_fee', '1');
                    break;
                case 'admission':
                    $query->where('fees.fp_main_admission_fee', '1');
                    break;
                case 'one_time':
                    $query->where('fees.fp_recurring', '0')->where('fees.fp_main_monthly_fee', '1');
                    break;
                case 'recurring':
                    $query->where('fees.fp_recurring', '1')->where('fees.fp_main_monthly_fee', '0');
                    break;
            }
        }

        // **Get Total Count for Pagination**
        $totalCount = $query->count();

        // **Fetch Paginated Results**
        $transactions = $query->offset($offset)->limit($limit)->get();

        // **Calculate Total Due & Total Paid Amount for the Current Page**
        $totalDueAmount = $transactions->where('payment_status', '0')->sum('total_amount');  // Unpaid fees
        $totalPaidAmount = $transactions->where('payment_status', '1')->sum('total_amount'); // Paid fees

        // **Format Response**
        $formattedTransactions = $transactions->map(function ($transaction, $index) use ($offset) {
            return [
                'SN' => (string)($offset + $index + 1),
                'Name' => $transaction->student_name,
                'Roll No' => $transaction->st_roll_no,
                'Fee Name' => $transaction->fee_name,
                'Base Amount' => (string) $transaction->base_amount,
                'Due Date' => $transaction->due_date,
                'Late Fee' => (string) $transaction->late_fee,
                'Concession' => (string) ($transaction->concession ?? '0'),
                'Total Amount' => (string) $transaction->total_amount,
                'Status' => $transaction->payment_status === '1' ? 'Paid' : 'Unpaid',
            ];
        });

        // **Return API Response**
        return $formattedTransactions->count() > 0
            ? response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Fees data fetched successfully.',
                'data' => $formattedTransactions,
                'total' => $totalCount,
                'offset' => (string) $offset,
                'limit' => (string) $limit,
                'page_total_due' => (string) $totalDueAmount,  // Total Unpaid Amount in Current Page
                'page_total_paid' => (string) $totalPaidAmount, // Total Paid Amount in Current Page
            ])
            : response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No fees found for the given criteria.',
            ]);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching fees data.',
            'error' => $e->getMessage(),
        ]);
    }
}
public function printFeeReceipt($id)
{
    try {
        // Fetch Fee Record
        $fee = FeeModel::findOrFail($id);
        
        // Fetch Student Record
        $student = StudentModel::findOrFail($fee->st_id);

        // Convert Fee Amount to Words
        $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($formatter->format($fee->f_total_paid)) . " only";

        // Prepare Data for Blade View
        $data = [
            'receipt_no' => $fee->id,
            'date' => Carbon::parse($fee->f_paid_date)->format('d-m-Y'),
            'name' => $student->st_first_name . ' ' . $student->st_last_name,
            'roll_no' => $student->st_roll_no,
            'class' => $student->class->cg_name ?? 'N/A', // Assuming class is related
            'amount' => number_format($fee->f_total_paid, 2),
            'amount_in_words' => strtoupper($amountInWords),
            'payment_method' => $fee->fpp_name ?? 'N/A',
            'status' => $fee->f_paid == '1' ? 'PAID' : 'UNPAID'
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.fee_receipt', $data)->setPaper('A4', 'portrait');

        // Define File Path
        $directory = "exports";
        $fileName = "FeeReceipt_" . now()->format('Y_m_d_H_i_s') . ".pdf";
        $fullPath = storage_path("app/public/{$directory}/{$fileName}");

        // Ensure the directory exists in storage
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Store the PDF in storage
        file_put_contents($fullPath, $pdf->output());
        $fullUrl = url("storage/exports/{$fileName}");

        // Return Response
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Fee Receipt PDF generated successfully.',
            'data' => [
                'file_url' => $fullUrl, // Full public URL
                'file_name' => $fileName,
                'file_size' => filesize($fullPath),
                'content_type' => 'application/pdf',
            ],
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'code' => 404,
            'status' => false,
            'message' => 'Fee record not found.',
            'error' => $e->getMessage(),
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while generating the PDF.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


private function exportExcel(array $data)
{
    // Define the directory and file name for storing the file
    $directory = "exports";
    $fileName = 'fees_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
    $fullPath = "{$directory}/{$fileName}";

    // Store the file in the 'public' disk under the exports directory
    Excel::store(new FeesExport(collect($data)), $fullPath, 'public');

    // Generate the public URL for the file
    $fullFileUrl = url('storage/' . $fullPath);

    // Return file metadata
    return response()->json([
        'code' => 200,
        'status' => true,
        'message' => 'File available for download',
        'data' => [
            'file_url' => $fullFileUrl,
            'file_name' => $fileName,
            'file_size' => Storage::disk('public')->size($fullPath),
            'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ]);
}

public function exportFees(Request $request)
{
    try {
        // Fetch data using existing logic from `index_all()`
        $transactions = $this->fetchFeesData($request); 

        if ($transactions->isEmpty()) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No data available for export.',
            ]);
        }

        // Convert collection to array for export
        return $this->exportExcel($transactions->toArray());

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while exporting data.',
            'error' => $e->getMessage(),
        ]);
    }
}
private function fetchFeesData(Request $request)
{
    $validated = $request->validate([
        'search'    => 'nullable|string|max:255',
        'cg_id'     => 'nullable|string',
        'status'    => 'nullable|in:paid,unpaid',
        'year'      => 'nullable|integer',
        'date_from' => 'nullable|date',
        'date_to'   => 'nullable|date|after_or_equal:date_from',
        'type'      => 'nullable|in:monthly,admission,one_time,recurring',
    ]);

    $query = DB::table('t_fees as fees')
        ->join('t_students as stu', 'fees.st_id', '=', 'stu.id')
        ->leftJoin('t_class_groups as cg', 'fees.cg_id', '=', 'cg.id')
        ->selectRaw("
            stu.st_roll_no,
            CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
            fees.fpp_name AS fee_name,
            fees.fpp_amount AS base_amount,
            fees.fpp_due_date AS due_date,
            IF(fees.f_late_fee_applicable = '1', fees.fpp_late_fee, '0') AS late_fee,
            fees.f_concession AS concession,
            (fees.fpp_amount + IF(fees.f_late_fee_applicable = '1', fees.fpp_late_fee, 0) - IFNULL(fees.f_concession, 0)) AS total_amount,
            IF(fees.f_paid = '1', '1', '0') AS payment_status
        ")
        ->orderBy('fees.fpp_due_date', 'asc');

    // Apply filters
    if (!empty($validated['search'])) {
        $searchTerm = '%' . trim($validated['search']) . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where('stu.st_roll_no', 'LIKE', $searchTerm)
              ->orWhere('stu.st_first_name', 'LIKE', $searchTerm)
              ->orWhere('stu.st_last_name', 'LIKE', $searchTerm);
        });
    }

    if (!empty($validated['cg_id'])) {
        $cgIds = explode(',', $validated['cg_id']);
        $query->whereIn('fees.cg_id', $cgIds);
    }

    if (!empty($validated['status'])) {
        $query->where('fees.f_paid', $validated['status'] === 'paid' ? '1' : '0');
    }

    if (!empty($validated['year'])) {
        $query->where('fees.fpp_year_no', $validated['year']);
    }

    if (!empty($validated['date_from'])) {
        $query->whereDate('fees.fpp_due_date', '>=', $validated['date_from']);
    }

    if (!empty($validated['date_to'])) {
        $query->whereDate('fees.fpp_due_date', '<=', $validated['date_to']);
    }

    if (!empty($validated['type'])) {
        switch ($validated['type']) {
            case 'monthly':
                $query->where('fees.fp_recurring', '1')->where('fees.fp_main_monthly_fee', '1');
                break;
            case 'admission':
                $query->where('fees.fp_main_admission_fee', '1');
                break;
            case 'one_time':
                $query->where('fees.fp_recurring', '0')->where('fees.fp_main_monthly_fee', '1');
                break;
            case 'recurring':
                $query->where('fees.fp_recurring', '1')->where('fees.fp_main_monthly_fee', '0');
                break;
        }
    }

    return $query->get();
}

public function getOneTimeFeePlans(Request $request)
{
    try {
        $validated = $request->validate([
            'ay_id' => 'required|integer|exists:t_academic_years,id',
        ]);

        $feePlans = DB::table('t_fee_plans')
            ->where('fp_recurring', '0')
            ->where('ay_id', $validated['ay_id'])
            ->select('id', 'fp_name')
            ->orderBy('id', 'desc')
            ->get();

        return $feePlans->isNotEmpty()
            ? response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'One-time fee plans fetched successfully.',
                'data' => $feePlans,
            ])
            : response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No one-time fee plans found for the selected academic year.',
            ]);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching fee plans.',
            'error' => $e->getMessage(),
        ]);
    }
}

}
