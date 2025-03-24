<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\StudentModel;
use App\Models\PGResponseModel;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionExport;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    //

    // public function importCsv(Request $request)
    // {
    //     // Increase memory and execution time limits for handling large files
    //     ini_set('max_execution_time', 300); // 5 minutes
    //     ini_set('memory_limit', '1024M');   // 1GB

    //     try {
    //         $csvFilePath = storage_path('app/public/txn.csv');

    //         // Check if the CSV file exists
    //         if (!file_exists($csvFilePath)) {
    //             return response()->json(['message' => 'CSV file not found.'], 404);
    //         }

    //         // Read the CSV file
    //         $csvContent = file_get_contents($csvFilePath);
    //         $csv = Reader::createFromString($csvContent);
    //         // Set the delimiter to tab (`\t`)
    //         //  $csv->setDelimiter("\t");
    //         $csv->setDelimiter(","); // Explicitly set the delimiter
    //         $csv->setHeaderOffset(0); // Use the first row as the header
    //         $records = (new Statement())->process($csv);

    //         $batchSize = 1000; // Number of records to process per batch
    //         $data = [];

    //         // Truncate the table before import
    //         TransactionModel::truncate();

    //         foreach ($records as $index => $row) {
    //             try {

    //                 $data[] = [
    //                     'id' => $row['txn_id'],
    //                     'st_id' => isset($row['st_id']) && trim($row['st_id']) !== '' ? $row['st_id'] : null,
    //                     'sch_id' => isset($row['sch_id']) && trim($row['sch_id']) !== '' ? $row['sch_id'] : null,
    //                     'txn_type_id' => isset($row['txn_type_id']) && trim($row['txn_type_id']) !== '' ? $row['txn_type_id'] : null,
    //                     'txn_date' => isset($row['txn_date']) && trim($row['txn_date']) !== '' ? $row['txn_date'] : null,
    //                     'txn_mode' => isset($row['txn_mode']) && trim($row['txn_mode']) !== '' ? $row['txn_mode'] : 'internal',
    //                     'txn_amount' => isset($row['txn_amount']) && is_numeric($row['txn_amount']) ? $row['txn_amount'] : 0.00,
    //                     'f_id' => isset($row['f_id']) && is_numeric($row['f_id']) ? $row['f_id'] : null, // NULL if invalid or missing
    //                     'f_normal' => isset($row['f_normal']) && in_array($row['f_normal'], ['0', '1']) ? $row['f_normal'] : '0',
    //                     'f_late' => isset($row['f_late']) && in_array($row['f_late'], ['0', '1']) ? $row['f_late'] : '0',
    //                     'txn_tagged_to_id' => isset($row['txn_tagged_to_id']) && is_numeric($row['txn_tagged_to_id']) ? $row['txn_tagged_to_id'] : null,
    //                     'txn_reason' => isset($row['txn_reason']) && trim($row['txn_reason']) !== '' ? $row['txn_reason'] : null,
    //                     'date' => isset($row['date']) && $row['date'] !== 'NULL' && trim($row['date']) !== '' ? $row['date'] : null,
    //                 ];

    //                 // Insert in batches
    //                 if (count($data) >= $batchSize) {
    //                     TransactionModel::insert($data);
    //                     $data = []; // Reset the batch
    //                 }
    //             } catch (\Exception $e) {
    //                 // Log the error for the specific row
    //                 Log::error("Error importing row {$index}: {$e->getMessage()}", ['row' => $row]);
    //             }
    //         }

    //         // Insert any remaining records
    //         if (!empty($data)) {
    //             TransactionModel::insert($data);
    //         }

    //         return response()->json(['message' => 'CSV imported successfully!'], 200);
    //     } catch (\Exception $e) {
    //         // Log and return the general error
    //         Log::error('Failed to import CSV: ' . $e->getMessage());
    //         return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
    //     }
    // }

   
    public function index(Request $request)
{
    try {
        // Validate filters
        $validated = $request->validate([
            'search'    => 'nullable|string|max:255', // Search by roll no or name
            'cg_id'     => 'nullable|string', // Multiple class IDs (comma-separated)
            'date_from' => 'nullable|date', // Start date
            'date_to'   => 'nullable|date|after_or_equal:date_from', // End date
            'offset'    => 'nullable|integer|min:0',
            'limit'     => 'nullable|integer|min:1|max:100',
        ]);

        // Set pagination defaults
        $offset = $validated['offset'] ?? 0;
        $limit  = $validated['limit'] ?? 10;

        // Start query using DB::table() for performance
        $query = DB::table('t_txns as txn')
            ->join('t_students as stu', 'txn.st_id', '=', 'stu.id')
            ->leftJoin('t_student_classes as sc', 'stu.id', '=', 'sc.st_id')
            ->leftJoin('t_class_groups as cg', 'sc.cg_id', '=', 'cg.id')
            ->leftJoin('t_txn_types as tt', 'txn.txn_type_id', '=', 'tt.id')
            ->leftJoin('t_fees as f', 'txn.f_id', '=', 'f.id')
            ->selectRaw("
                stu.st_roll_no,
                stu.id as student_id,
                CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
                COALESCE(cg.cg_name, 'N/A') AS class_name,
                txn.txn_amount as amount,
                txn.txn_mode as mode,
                txn.txn_date,
                txn.txn_time,
                tt.txn_type_from AS txn_from,
                tt.txn_type_to AS txn_to,
                COALESCE(f.fpp_name, 'Payment from student') AS narration
            ");

        // Apply search filter (Student Roll No or Name)
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('stu.st_roll_no', 'LIKE', $searchTerm)
                  ->orWhere('stu.st_first_name', 'LIKE', $searchTerm)
                  ->orWhere('stu.st_last_name', 'LIKE', $searchTerm);
            });
        }

        // Apply class filter (Multiple `cg_id` values)
        if (!empty($validated['cg_id'])) {
            $cgIds = explode(',', $validated['cg_id']); // Convert comma-separated IDs to array
            $query->whereIn('sc.cg_id', $cgIds);
        }

        // Apply date filter
        if (!empty($validated['date_from'])) {
            $query->whereDate('txn.txn_date', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->whereDate('txn.txn_date', '<=', $validated['date_to']);
        }

        // Optimize by limiting & paginating results
        $total_count = $query->count();
        $transactions = $query
            ->orderBy('txn.txn_date', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'code'      => 200,
            'status'    => true,
            'message'   => 'Transactions fetched successfully.',
            'data'      => $transactions,
            'total'     => $total_count,
            'count'     => count($transactions),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'code'    => 500,
            'status'  => false,
            'message' => 'An error occurred while fetching transactions.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
   public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '1024M');   // 1GB

        try {
            $csvFilePath = storage_path('app/public/txn.csv');

            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read the CSV file
            $csvContent = file_get_contents($csvFilePath);
            $csv = Reader::createFromString($csvContent);
            // $csv->setDelimiter("\t");
            $csv->setDelimiter(","); // Explicitly set the delimiter
            $csv->setHeaderOffset(0); // Use the first row as the header
            $records = (new Statement())->process($csv);

            $batchSize = 1000; // Number of records per batch
            $data = [];

            // Truncate the table before import
            TransactionModel::truncate();

            foreach ($records as $index => $row) {
                try {
                    // Parse txn_date and split into date and time
                    $txnDateTime = isset($row['txn_date']) && is_numeric($row['txn_date']) ? (int)$row['txn_date'] : null;
                    $txnDate = null;
                    $txnTime = null;

                    if ($txnDateTime) {
                        $txnDate = date('Y-m-d', $txnDateTime); // Convert to YYYY-MM-DD format
                        $txnTime = date('H:i:s', $txnDateTime); // Convert to HH:MM:SS format
                    }

                    $data[] = [
                        'id' => $row['txn_id'],
                        'st_id' => isset($row['st_id']) && trim($row['st_id']) !== '' ? $row['st_id'] : null,
                        'sch_id' => isset($row['sch_id']) && trim($row['sch_id']) !== '' ? $row['sch_id'] : null,
                        'txn_type_id' => isset($row['txn_type_id']) && trim($row['txn_type_id']) !== '' ? $row['txn_type_id'] : null,
                        'txn_date' => $txnDate,
                        'txn_time' => $txnTime,
                        'txn_mode' => isset($row['txn_mode']) && trim($row['txn_mode']) !== '' ? $row['txn_mode'] : 'internal',
                        'txn_amount' => isset($row['txn_amount']) && is_numeric($row['txn_amount']) ? $row['txn_amount'] : 0.00,
                        'f_id' => isset($row['f_id']) && is_numeric($row['f_id']) ? $row['f_id'] : null,
                        'f_normal' => isset($row['f_normal']) && in_array($row['f_normal'], ['0', '1']) ? $row['f_normal'] : '0',
                        'f_late' => isset($row['f_late']) && in_array($row['f_late'], ['0', '1']) ? $row['f_late'] : '0',
                        'txn_tagged_to_id' => isset($row['txn_tagged_to_id']) && is_numeric($row['txn_tagged_to_id']) ? $row['txn_tagged_to_id'] : null,
                        'txn_reason' => isset($row['txn_reason']) && trim($row['txn_reason']) !== '' ? $row['txn_reason'] : null,
                        'date' => isset($row['date']) && $row['date'] !== 'NULL' && trim($row['date']) !== '' ? $row['date'] : null,
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        TransactionModel::insert($data);
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    // Log the error for the specific row
                    Log::error("Error importing row {$index}: {$e->getMessage()}", ['row' => $row]);
                }
            }

            // Insert any remaining records
            if (!empty($data)) {
                TransactionModel::insert($data);
            }

            return response()->json(['message' => 'CSV imported successfully!'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }
    public function addMoneyToWallet(Request $request)
{
    try {
        // Validate request data
        $validated = $request->validate([
            'st_id'       => 'required|integer|exists:t_students,id',
            'amount'      => 'required|numeric|min:1',
            'type'        => 'required|in:draft,pg,neft,transport', // Allowed types
            'date'        => 'required|date',
            'comments'    => 'nullable|string|max:255',

            // Conditional validation based on type
            'bank_name'   => 'required_if:type,draft|string|max:255',
            'draft_no'    => 'required_if:type,draft|string|max:50',
            'draft_date'  => 'required_if:type,draft|date',

            'transaction_id'   => 'required_if:type,pg,neft|string|max:255',
            'transaction_date' => 'required_if:type,pg,neft|date',

            'receipt_no'  => 'required_if:type,transport|string|max:255',
        ]);

        // Fetch student details
        $student = StudentModel::find($validated['st_id']);

        if (!$student) {
            return response()->json([
                'code'    => 404,
                'status'  => false,
                'message' => 'Student not found.',
            ], 404);
        }

        // Define transaction mode based on type
        $txnMode = match ($validated['type']) {
            'draft'    => 'draft',
            'pg'       => 'pg',
            'neft'     => 'neft',
            'transport'=> 'transport',
            default    => 'internal',
        };

        // Prepare `txn_reason` JSON data
        $txnDetails = [
            'comments' => $validated['comments'] ?? null, // Include user comments
        ];

        if ($validated['type'] === 'draft') {
            $txnDetails['bank_name'] = $validated['bank_name'];
            $txnDetails['draft_no'] = $validated['draft_no'];
            $txnDetails['draft_date'] = $validated['draft_date'];
        } elseif (in_array($validated['type'], ['pg', 'neft'])) {
            $txnDetails['transaction_id'] = $validated['transaction_id'];
            $txnDetails['transaction_date'] = $validated['transaction_date'];
        } elseif ($validated['type'] === 'transport') {
            $txnDetails['receipt_no'] = $validated['receipt_no'];
        }

        // Convert to JSON and store in txn_reason
        $txnReason = json_encode($txnDetails);

        // Create a new transaction record in `t_transactions`
        $transaction = new TransactionModel();
        $transaction->st_id         = $validated['st_id'];
        $transaction->sch_id        = 1; // Always remains 1
        $transaction->txn_type_id   = 1; // Always 1
        $transaction->txn_date      = $validated['date'];
        $transaction->txn_time      = now()->format('H:i:s');
        $transaction->txn_mode      = $txnMode;
        $transaction->txn_amount    = $validated['amount'];
        $transaction->txn_reason    = $txnReason; // Store additional fields in JSON format
        $transaction->f_id          = null;
        $transaction->f_normal      = '0';
        $transaction->f_late        = '0';
        $transaction->txn_tagged_to_id = null;
        $transaction->date          = now();

        $transaction->save();

        // Update student's wallet balance
        $student->st_wallet += $validated['amount'];
        $student->save();

        return response()->json([
            'code'    => 200,
            'status'  => true,
            'message' => 'Money added to wallet successfully.',
            'data'    => [
                'student_id'  => $validated['st_id'],
                'new_balance' => $student->st_wallet,
                'transaction' => [
                    'txn_id'     => $transaction->id,
                    'txn_amount' => $transaction->txn_amount,
                    'txn_mode'   => $transaction->txn_mode,
                    'txn_reason' => json_decode($transaction->txn_reason, true), // Convert JSON back for response
                ],
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'code'    => 500,
            'status'  => false,
            'message' => 'An error occurred while adding money to wallet.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function index2(Request $request)
{
    try {
        // Validate filters
        $validated = $request->validate([
            'search'    => 'nullable|string|max:255', // Search by Roll No, Name, or Reference No
            'cg_id'     => 'nullable|string', // Multiple class IDs (comma-separated)
            'mode'      => 'nullable|string|max:255', // Payment Mode filter
            'status'    => 'nullable|in:success,pending', // Filter by Status
            'date_from' => 'nullable|date', // Start date filter
            'date_to'   => 'nullable|date|after_or_equal:date_from', // End date filter
            'offset'    => 'nullable|integer|min:0', // Pagination offset
            'limit'     => 'nullable|integer|min:1|max:100', // Pagination limit
        ]);

        // Set default pagination values
        $offset = $validated['offset'] ?? 0;
        $limit = $validated['limit'] ?? 10;

        // **Query for PG Transactions (`t_pg_responses`)**
        $query = DB::table('t_pg_responses as pg')
            ->join('t_students as stu', 'pg.submerchant_id', '=', 'stu.id') // Corrected student reference
            ->leftJoin('t_student_classes as sc', 'stu.id', '=', 'sc.st_id')
            ->leftJoin('t_class_groups as cg', 'sc.cg_id', '=', 'cg.id')
            ->selectRaw("
                stu.st_roll_no,
                stu.id as student_id,
                CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
                COALESCE(cg.cg_name, 'N/A') AS class_name,
                pg.payment_mode AS mode,
                pg.transaction_date,
                pg.transaction_time,
                pg.unique_ref_number,
                pg.total_amount,
                pg.response_code
            ")
            ->orderBy('pg.transaction_date', 'desc')
            ->orderBy('pg.transaction_time', 'desc');

        // **Apply Search Filter (Roll No, Name, Reference No)**
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('stu.st_roll_no', 'LIKE', $searchTerm)
                  ->orWhere('student_name', 'LIKE', $searchTerm)
                  ->orWhere('pg.unique_ref_number', 'LIKE', $searchTerm);
            });
        }

        // **Apply Class Filter (`cg_id`)**
        if (!empty($validated['cg_id'])) {
            $cgIds = explode(',', $validated['cg_id']);
            $query->whereIn('sc.cg_id', $cgIds);
        }

        // **Apply Payment Mode Filter**
        if (!empty($validated['mode'])) {
            $query->where('pg.payment_mode', $validated['mode']);
        }

        // **Apply Status Filter (`E000` = Success, others = Pending)**
        if (!empty($validated['status'])) {
            if ($validated['status'] === 'success') {
                $query->where('pg.response_code', 'E000');
            } else {
                $query->where('pg.response_code', '<>', 'E000'); // Any response code except 'E000'
            }
        }

        // **Apply Date Filters**
        if (!empty($validated['date_from'])) {
            $query->whereDate('pg.transaction_date', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->whereDate('pg.transaction_date', '<=', $validated['date_to']);
        }

        // **Get Total Count for Pagination**
        $totalCount = $query->count();

        // **Fetch Paginated Results**
        $transactions = $query->offset($offset)->limit($limit)->get();

        // **Calculate Total Amount for Current Page**
        $totalAmountForPage = $transactions->sum('total_amount');

        // **Format Response**
        $formattedTransactions = $transactions->map(function ($transaction, $index) use ($offset) {
            return [
                'SN' => $offset + $index + 1,
                'Name' => $transaction->student_name,
                'Roll No' => $transaction->st_roll_no,
                'Class' => $transaction->class_name,
                'Date' => "{$transaction->transaction_date} {$transaction->transaction_time}",
                'Unique_Ref_No' => $transaction->unique_ref_number,
                'Total_Amount' => $transaction->total_amount,
                'Status' => $transaction->response_code === 'E000' ? 'Success' : 'Pending',
                'Mode' => $transaction->mode,
            ];
        });

        // **Return API Response**
        return $formattedTransactions->count() > 0
            ? response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Transactions fetched successfully.',
                'data' => $formattedTransactions,
                'total' => $totalCount,
                'offset' => $offset,
                'limit' => $limit,
                'page_total_amount' => $totalAmountForPage,
            ])
            : response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No transactions found for the given criteria.',
            ]);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching transactions.',
            'error' => $e->getMessage(),
        ]);
    }
}
public function export(Request $request)
{
    try {
        // Validate request parameters
        $validated = $request->validate([
            'search'    => 'nullable|string|max:255', // Search by Roll No, Name, or Reference No
            'cg_id'     => 'nullable|string', // Multiple class IDs (comma-separated)
            'mode'      => 'nullable|string|max:255', // Payment Mode filter
            'status'    => 'nullable|in:success,pending', // Filter by Status
            'date_from' => 'nullable|date', // Start date filter
            'date_to'   => 'nullable|date|after_or_equal:date_from', // End date filter
        ]);

        // Debugging: Log input filters
        Log::info("Export Request Filters", $validated);

        // **Query for PG Transactions (`t_pg_responses`)**
        $query = DB::table('t_pg_responses as pg')
            ->join('t_students as stu', 'pg.submerchant_id', '=', 'stu.id')
            ->leftJoin('t_student_classes as sc', 'stu.id', '=', 'sc.st_id')
            ->leftJoin('t_class_groups as cg', 'sc.cg_id', '=', 'cg.id')
            ->selectRaw("
                stu.st_roll_no,
                stu.id as student_id,
                CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
                COALESCE(cg.cg_name, 'N/A') AS class_name,
                pg.payment_mode AS mode,
                pg.transaction_date,
                pg.transaction_time,
                pg.unique_ref_number,
                pg.total_amount,
                pg.response_code
            ")
            ->orderBy('pg.transaction_date', 'desc')
            ->orderBy('pg.transaction_time', 'desc');

        // **Apply Filters**

        // **Search Filter**
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('stu.st_roll_no', 'LIKE', $searchTerm)
                  ->orWhereRaw("CONCAT(stu.st_first_name, ' ', stu.st_last_name) LIKE ?", [$searchTerm])
                  ->orWhere('pg.unique_ref_number', 'LIKE', $searchTerm);
            });
        }

        // **Class Filter**
        if (!empty($validated['cg_id'])) {
            $cgIds = explode(',', $validated['cg_id']);
            $query->whereIn('sc.cg_id', $cgIds);
        }

        // **Payment Mode Filter**
        if (!empty($validated['mode'])) {
            $query->where('pg.payment_mode', $validated['mode']);
        }

        // **Status Filter**
        if (!empty($validated['status'])) {
            if ($validated['status'] === 'success') {
                $query->where('pg.response_code', 'E000');
            } else {
                $query->where('pg.response_code', '<>', 'E000');
            }
        }

        // **Date Filters (Apply only if provided)**
        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            Log::info("Filtering Transactions From: {$validated['date_from']} To: {$validated['date_to']}");
            $query->whereBetween(DB::raw("DATE(pg.transaction_date)"), [$validated['date_from'], $validated['date_to']]);
        }

        // **Fetch Transactions**
        $transactions = $query->get();

        Log::info("Total Transactions Found: " . $transactions->count());

        if ($transactions->isEmpty()) {
            return response()->json(['code'=>500,'message' => 'No data available for export.'], 404);
        }

        // **Map Data for Export**
        $transactions = $transactions->map(function ($transaction, $index) {
            return [
                'SN' => $index + 1,
                'Name' => $transaction->student_name,
                'Roll No' => $transaction->st_roll_no,
                'Class' => $transaction->class_name,
                'Date' => "{$transaction->transaction_date} {$transaction->transaction_time}",
                'Unique Ref No' => $transaction->unique_ref_number,
                'Total Amount' => $transaction->total_amount,
                'Status' => $transaction->response_code === 'E000' ? 'Success' : 'Pending',
                'Mode' => $transaction->mode,
            ];
        })->toArray();

        // **Export as Excel**
        return $this->exportExcel($transactions);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while exporting transactions.',
            'error' => $e->getMessage(),
        ]);
    }
}

    private function exportExcel(array $data)
    {
        // Define export file name
        $directory = "exports";
        $fileName = 'Transactions_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $fullPath = "{$directory}/{$fileName}";

        // Store Excel file
        Excel::store(new TransactionExport($data), $fullPath, 'public');

        // Return file download URL
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'File available for download.',
            'data' => [
                'file_url' => url('storage/' . $fullPath),
                'file_name' => $fileName,
                'file_size' => Storage::disk('public')->size($fullPath),
                'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ]);
    }
    public function getStudentTransactions(Request $request)
    {
        try {
            // ✅ Validate request
            $validated = $request->validate([
                'st_id'  => 'required|integer|exists:t_txns,st_id', // Must exist in transactions
                'offset' => 'nullable|integer|min:0',
                'limit'  => 'nullable|integer|min:1|max:100', // Max 100 records per request
            ]);
    
            // ✅ Set Pagination Defaults
            $offset = $validated['offset'] ?? 0;
            $limit  = $validated['limit'] ?? 10;
    
            // ✅ Fetch Transactions for the Given Student (`st_id`)
            $transactionsQuery = DB::table('t_txns as txn')
                ->join('t_students as stu', 'txn.st_id', '=', 'stu.id')
                ->leftJoin('t_student_classes as sc', 'stu.id', '=', 'sc.st_id')
                ->leftJoin('t_class_groups as cg', 'sc.cg_id', '=', 'cg.id')
                ->leftJoin('t_txn_types as tt', 'txn.txn_type_id', '=', 'tt.id')
                ->leftJoin('t_fees as f', 'txn.f_id', '=', 'f.id')
                ->selectRaw("
                    txn.id as txn_id,
                    stu.st_roll_no,
                    stu.id as student_id,
                    CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS student_name,
                    COALESCE(cg.cg_name, 'N/A') AS class_name,
                    txn.txn_amount as amount,
                    txn.txn_mode as mode,
                    txn.txn_date,
                    txn.txn_time,
                    tt.txn_type_from AS txn_from,
                    tt.txn_type_to AS txn_to,
                    txn.txn_reason,
                    COALESCE(f.fpp_name, 'Payment from student') AS narration
                ")
                ->where('txn.st_id', $validated['st_id'])
                ->orderBy('txn.txn_date', 'desc') // ✅ Sort by latest first
                ->offset($offset)
                ->limit($limit);
    
            // ✅ Fetch Data
            $total_count = $transactionsQuery->count();
            $transactions = $transactionsQuery->get();
    
            // ✅ Return JSON Response
            return response()->json([
                'code'      => 200,
                'status'    => true,
                'message'   => 'Student transactions fetched successfully.',
                'data'      => $transactions,
                'total'     => $total_count,
                'count'     => count($transactions),
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 500,
                'status'  => false,
                'message' => 'An error occurred while fetching transactions.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
