<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceModel;
use App\Models\ClassGroupModel;
use App\Models\AcademicYearModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class AttendanceController extends Controller
{
    //
    // public function register(Request $request)
    // {
    //     // Validation rules
    //     $validated = $request->validate([
    //         'class_name' => 'required|string|exists:t_class_groups,cg_name', // Ensure the class name exists in the class table
    //         'total_days' => 'required|integer|min:1', // Total days passed from frontend
    //         'attendance' => 'required|integer', // Attendance information
    //         'st_roll_no' => 'required|string|exists:t_students,st_roll_no', // Student roll number
    //     ]);

    //     try {
    //         // Retrieve the class group ID from the class name
    //         $classGroup = ClassGroupModel::where('cg_name', $validated['class_name'])->first();

    //         if (!$classGroup) {
    //             return response()->json(['message' => 'Invalid class name provided.'], 400);
    //         }

    //         // Create a new attendance record column by column
    //         $attendance = AttendanceModel::create([
    //             'cg_id' => $classGroup->id,                   // Class group ID from the table
    //             'term' => 1,                                  // Term is set to 1
    //             'unit' => 1,                                  // Unit is set to 1
    //             'session' => now()->format('Y'),              // Session year
    //             'st_roll_no' => $validated['st_roll_no'],     // Student roll number
    //             'attendance' => $validated['attendance'],     // Attendance status
    //             'total_days' => $validated['total_days'],     // Total days from frontend
    //         ]);

    //         return response()->json([
    //             'message' => 'Attendance record created successfully.',
    //             'data' => $attendance->makeHidden(['id', 'created_at', 'updated_at']),
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to register attendance.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function registerandUpdate(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'class_name' => 'required|string|exists:t_class_groups,cg_name', // Ensure the class name exists in the class table
            'total_days' => 'required|integer|min:1', // Total days passed from frontend
            'attendance' => 'required|integer', // Attendance information
            'st_roll_no' => 'required|string|exists:t_students,st_roll_no', // Student roll number
            'ay_name' => 'nullable|string|exists:t_academic_years,ay_name', // Optional academic year name
        ]);

        try {
            
            // Retrieve the academic year if `ay_name` is passed, otherwise use the current year
            $academicYear = isset($validated['ay_name']) && !empty($validated['ay_name'])
            ? AcademicYearModel::where('ay_name', $validated['ay_name'])->first()
            : AcademicYearModel::where('ay_name', now()->year . '-' . substr((now()->year + 1), -2))->first();

            if (!$academicYear) {
                return response()->json(['message' => 'Invalid or missing academic year.'], 400);
            }

            // Retrieve the class group ID from the class name
            $classGroup = ClassGroupModel::where('cg_name', $validated['class_name'])->first();

            if (!$classGroup) {
                return response()->json(['message' => 'Invalid class name provided.'], 400);
            }

            // Check if attendance already exists for the same st_roll_no and class_name
            $existingAttendance = AttendanceModel::where('st_roll_no', $validated['st_roll_no'])
                ->where('cg_id', $classGroup->id)
                ->where('session', $academicYear->id) // Match academic year
                ->first();

            if ($existingAttendance) {
                // Update the existing record
                $existingAttendance->update([
                    'term' => 1,                                  // Term is set to 1
                    'unit' => 1,                                  // Unit is set to 1
                    'attendance' => $validated['attendance'],     // Attendance status
                    'total_days' => $validated['total_days'],     // Total days from frontend
                ]);

                return response()->json([
                    'message' => 'Attendance record updated successfully.',
                    'data' => $existingAttendance->makeHidden(['id', 'created_at', 'updated_at']),
                ], 200);
            } else {
                // Create a new attendance record
                $attendance = AttendanceModel::create([
                    'cg_id' => $classGroup->id,                   // Class group ID from the table
                    'term' => 1,                                  // Term is set to 1
                    'unit' => 1,                                  // Unit is set to 1
                    'session' => $academicYear->id,          // Session year from academic year
                    'st_roll_no' => $validated['st_roll_no'],     // Student roll number
                    'attendance' => $validated['attendance'],     // Attendance status
                    'total_days' => $validated['total_days'],     // Total days from frontend
                ]);

                return response()->json([
                    'message' => 'Attendance record created successfully.',
                    'data' => $attendance->makeHidden(['id', 'created_at', 'updated_at']),
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process attendance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


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
                        'id' => $row['id'],
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

    public function index()
    {
        // Fetch all records from the model
        $get_records = AttendanceModel::all(); // Replace FeeModel with your model name

        return $get_records->isNotEmpty()
            ? response()->json([
                'message' => 'Records fetched successfully!',
                'data' => $get_records->makeHidden(['id', 'created_at', 'updated_at']),
                'count' => $get_records->count(),
            ], 200)
            : response()->json(['message' => 'No records available.'], 404);
        }

}
