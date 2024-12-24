<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class AttendanceController extends Controller
{
    //
    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/studAttendance.csv');

            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read and parse the CSV file
            $csvContent = file_get_contents($csvFilePath);
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0); // Use the first row as the header
            $records = (new Statement())->process($csv);

            $batchSize = 500; // Number of records to process in one batch
            $attendanceData = [];

            // Truncate table before import
            AttendanceModel::truncate();

            Log::info('Starting CSV processing for attendance...');
            DB::beginTransaction();

            foreach ($records as $index => $row) {
                try {
                    // Prepare attendance data
                    $attendance = [
                        'session' => $row['session'] ?? '',
                        'st_roll_no' => $row['st_roll_no'] ?? '',
                        'cg_id' => $row['cg_id'] ?? '',
                        'term' => $row['term'] ?? '',
                        'unit' => $row['unit'] ?? '',
                        'attendance' => $row['attendance'] ?? '',
                        'total_days' => $row['total_days'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $attendanceData[] = $attendance;

                    // Insert in batches
                    if (count($attendanceData) >= $batchSize) {
                        AttendanceModel::insert($attendanceData);
                        Log::info("Inserted a batch of attendance records.");
                        $attendanceData = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($attendanceData) > 0) {
                AttendanceModel::insert($attendanceData);
                Log::info("Inserted the remaining attendance records.");
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
