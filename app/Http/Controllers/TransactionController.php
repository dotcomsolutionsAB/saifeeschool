<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

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
}
