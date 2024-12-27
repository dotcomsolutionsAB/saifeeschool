<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\SubjectModel;

class SubjectController extends Controller
{
    //
    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time for large files
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // Define the path to the CSV file
            // $csvFilePath = $request->file('csv_file')->getRealPath();
            $csvFilePath = storage_path('app/public/studSubj.csv');

            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read the CSV file
            $csv = Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset(0); // First row as headers
            $records = (new Statement())->process($csv);

            $batchSize = 1000; // Number of records to process in one batch
            $data = [];

            // Truncate the table before import (if necessary)
            SubjectModel::truncate();

            DB::beginTransaction();

            foreach ($records as $index => $row) {
                try {
                    // Validate and prepare the data
                    $data[] = [
                        'subject' => $row['subject'] ?? null,
                        'cg_group' => $row['cg_group'] ?? null,
                        'type' => $row['type'] ?? null,
                        'serial' => $row['serial'] ?? null,
                        'category' => $row['category'] ?? null,
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        SubjectModel::insert($data);
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                SubjectModel::insert($data);
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
