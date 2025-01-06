<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PGResponseModel;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;

class PGResponseController extends Controller
{
    //
    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            $csvFilePath = storage_path('app/public/pg_response.csv');

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
            PGResponseModel::truncate();

            DB::beginTransaction();
            foreach ($records as $index => $row) {
                try {
                    // Parse Transaction Date and Time
                    $transactionDate = null;
                    $transactionTime = null;

                    if (!empty(trim($row['Transaction_Date'] ?? ''))) {
                        $transactionDateTime = Carbon::parse($row['Transaction_Date']);
                        $transactionDate = $transactionDateTime->toDateString();
                        $transactionTime = $transactionDateTime->toTimeString();
                    }
                    // Process TPS field
                    $tps = in_array($row['TPS'], ['Y', 'N']) ? $row['TPS'] : null;

                    // Convert fields to appropriate formats
                    $uniqueRefNumber = (int)$row['Unique_Ref_Number'];
                    $submerchantId = (int)$row['SubMerchantId'];
                    $referenceNo = (int)$row['ReferenceNo'];
                    $icid = (int)$row['ICID'];

                    $data[] = [
                        'id' => $row['id'],
                        'response_code' => $row['Response_Code'] ?? '',
                        'unique_ref_number' => $uniqueRefNumber,
                        'transaction_date' => $transactionDate,
                        'transaction_time' => $transactionTime,
                        'total_amount' => (float)$row['Total_Amount'],
                        'interchange_value' => $row['Interchange_Value'] ?? null, // Keep as string
                        'tdr' => $row['TDR'] ?? null, // Keep as string
                        'payment_mode' => $row['Payment_Mode'] ?? '',
                        'submerchant_id' => $submerchantId,
                        'reference_no' => $referenceNo,
                        'icid' => $icid,
                        'rs' => $row['RS'] ?? '',
                        'tps' => $tps, // Only 'Y' or 'N' values stored
                        'mandatory_fields' => $row['mandatory_fields'] ?? '',
                        'optional_fields' => $row['optional_fields'] !== 'null' ? $row['optional_fields'] : null, // Explicit handling for null
                        'rsv' => $row['RSV'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        PGResponseModel::insert($data);
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                PGResponseModel::insert($data);
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
