<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\StudentModel;

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

        // Start query from transactions table
        $query = TransactionModel::with(['student', 'txnType'])
            ->join('t_students', 't_transactions.st_id', '=', 't_students.id')
            ->leftJoin('t_student_classes', 't_students.id', '=', 't_student_classes.st_id')
            ->leftJoin('t_class_groups', 't_student_classes.cg_id', '=', 't_class_groups.id')
            ->leftJoin('t_txn_types', 't_transactions.txn_type_id', '=', 't_txn_types.id')
            ->leftJoin('t_fees', 't_transactions.f_id', '=', 't_fees.id');

        // Apply search filter (Student Roll No or Name)
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('t_students.st_roll_no', 'LIKE', $searchTerm)
                  ->orWhere('t_students.st_first_name', 'LIKE', $searchTerm)
                  ->orWhere('t_students.st_last_name', 'LIKE', $searchTerm);
            });
        }

        // Apply class filter (Multiple `cg_id` values)
        if (!empty($validated['cg_id'])) {
            $cgIds = explode(',', $validated['cg_id']); // Convert comma-separated IDs to array
            $query->whereIn('t_student_classes.cg_id', $cgIds);
        }

        // Apply date filter
        if (!empty($validated['date_from'])) {
            $query->whereDate('t_transactions.txn_date', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->whereDate('t_transactions.txn_date', '<=', $validated['date_to']);
        }

        // Fetch transactions with required data
        $transactions = $query
            ->select([
                't_students.st_roll_no',
                't_students.id as student_id',
                't_students.st_first_name',
                't_students.st_last_name',
                't_class_groups.cg_name as class_name',
                't_transactions.txn_amount as amount',
                't_transactions.txn_mode as mode',
                't_transactions.txn_date',
                't_transactions.txn_time',
                't_transactions.txn_reason',
                't_fees.fpp_name as fee_name',
                't_txn_types.txn_type_name',
                't_txn_types.txn_type_from',
                't_txn_types.txn_type_to',
                't_transactions.f_id'
            ])
            ->orderBy('t_transactions.txn_date', 'desc')
            ->get();

        // Transform data with correct narration
        $transactions = $transactions->map(function ($txn) {
            return [
                'student_roll_no' => $txn->st_roll_no,
                'student_id'      => $txn->student_id,
                'student_name'    => $txn->st_first_name . ' ' . $txn->st_last_name,
                'class'           => $txn->class_name ?? 'N/A',
                'amount'          => $txn->amount,
                'mode'            => $txn->mode,
                'txn_date'        => $txn->txn_date,
                'txn_time'        => $txn->txn_time,
                'txn_from'        => $txn->txn_type_from,
                'txn_to'          => $txn->txn_type_to,
                'narration'       => $txn->f_id ? $txn->fee_name : "Payment from student"
            ];
        });

        // Paginate results
        $total_count = $transactions->count();
        $transactions = $transactions->slice($offset, $limit)->values();

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
}
