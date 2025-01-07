<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionTypeModel;
use App\Models\TransactionModel;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\Log;

class TransactionTypeController extends Controller
{
    //
    public function importCsv(Request $request)
    {
        // Increase memory and execution time limits for handling large files
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '1024M');   // 1GB

        try {
            $csvFilePath = storage_path('app/public/txn_type.csv');

            // Check if the CSV file exists
            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read the CSV file
            $csvContent = file_get_contents($csvFilePath);
            $csv = Reader::createFromString($csvContent);
            $csv->setDelimiter(","); // Explicitly set the delimiter
            $csv->setHeaderOffset(0); // Use the first row as the header
            $records = (new Statement())->process($csv);

            $batchSize = 1000; // Number of records to process per batch
            $data = [];

            // Truncate the table before import
            TransactionTypeModel::truncate();

            foreach ($records as $index => $row) {
                try {
                    $data[] = [
                        'id' => $row['txn_type_id'],
                        'txn_type_from' => $row['txn_type_from'] ?? 'student', // Default to 'student'
                        'txn_type_to' => $row['txn_type_to'] ?? 'wallet',     // Default to 'wallet'
                        'txn_type_name' => isset($row['txn_type_name']) && trim($row['txn_type_name']) !== '' ? $row['txn_type_name'] : null,
                        'txn_type_description' => isset($row['txn_type_description']) && trim($row['txn_type_description']) !== '' ? $row['txn_type_description'] : null,
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        TxnTypeModel::insert($data);
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    // Log the error for the specific row
                    Log::error("Error importing row {$index}: {$e->getMessage()}", ['row' => $row]);
                }
            }

            // Insert any remaining records
            if (!empty($data)) {
                TransactionTypeModel::insert($data);
            }

            return response()->json(['message' => 'CSV imported successfully!'], 200);
        } catch (\Exception $e) {
            // Log and return the general error
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }

    public function fetchTransactions()
    {
        // Get the current year
        $currentYear = now()->year;

        // Fetch transactions for the current year
        // $transactions = TransactionModel::with(['student', 'txnType', 'txnDetail'])
        $transactions = TransactionModel::with(['student', 'txnType'])
            ->whereYear('txn_date', $currentYear) // Filter by current year
            ->get()
            ->map(function ($txn, $index) {
                return [
                    // 'txn_id' => $txn->txnDetail->txndet_pg_icici_id ?? '---',        // Use txnDetail's ICICI ID
                    'SN' =>  $index + 1,
                    'name' => $txn->student
                        ? $txn->student->st_first_name . ' ' . $txn->student->st_last_name
                        : 'N/A',                                                    // Concatenate first and last name
                    'roll_no' => $txn->student->st_roll_no ?? 'N/A',                 // Student roll number
                    'date' => $txn->txn_date,                                        // Transaction date
                    'from' => $txn->txnType->txn_type_from ?? 'N/A',                 // From column from txn_type
                    'to' => $txn->txnType->txn_type_to ?? 'N/A',                     // To column from txn_type
                    'narration' => $txn->txnType->txn_type_name ?? 'N/A',            // Narration from txn_type
                    'mode' => $txn->txn_mode === 'pg'
                        ? 'Online <br/>'
                        : '---',                                                    // Mode logic
                    'amount' => $txn->txn_amount,                                    // Amount from t_txns
                ];
            });

        return $transactions->count() > 0 
        ? response()->json(['status' => 'success', 'data' => $transactions, 'count' => count($transactions)]) 
        : response()->json(['status' => 'error', 'message' => 'No transactions found.']);
    }

}
