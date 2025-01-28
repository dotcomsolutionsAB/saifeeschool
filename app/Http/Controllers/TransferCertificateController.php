<?php

namespace App\Http\Controllers;
use App\Models\TransferCertificateModel;
use App\Models\StudentModel;
use App\Models\CounterModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class TransferCertificateController extends Controller
{
    //
    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            'st_roll_no' => 'required', // Student roll number (used for registration_no and st_roll_no)
            'name' => 'required|string|max:512', // Student name
            'father_name' => 'required|string|max:256', // Father's name
            'joining_class' => 'nullable|string|max:100', // Joining class
            'joining_date' => 'required|string', // Joining date
            'leaving_date' => 'nullable|string', // Leaving date
            'prev_school' => 'required|string|max:256', // Previous school
            'character' => 'required|string|max:100', // Character
            'class' => 'required|string|max:100', // Class
            'stream' => 'nullable|string|max:100', // Stream
            'date_from' => 'required|string', // Date from
            'date_to' => 'nullable|string', // Date to
            'dob' => 'required|string', // Date of birth
            'promotion' => 'required|in:Not Applicable,Refused,Promoted', // Promotion status
        ]);

        try {
            // Find all matching students by concatenating `st_first_name` and `st_last_name`
            $students = StudentModel::whereRaw("CONCAT(st_first_name, ' ', st_last_name) = ?", [$validated['name']])->get();

            if ($students->isEmpty()) {
                return response()->json(['message' => 'No student found with the provided name.'], 404);
            }

            // Call the CounterController increment function for serial number
            $counterRequest = new Request(['t_name' => 't_transfer_certificate']);
            $counterController = new CounterController();
            $incrementResponse = $counterController->increment($counterRequest);

            if ($incrementResponse->getStatusCode() !== 200) {
                return response()->json(['message' => 'Failed to increment serial number.'], 500);
            }

            $serialNo = $incrementResponse->getData()->data->number;

            $data = [
                'dated' => now()->toDateString(), // Current date
                'serial_no' => $serialNo, // Unique serial number
                'registration_no' => $validated['st_roll_no'], // Use st_roll_no for registration_no
                'st_id' => $students->first()->id, // Assuming the first matched student
                'st_roll_no' => $validated['st_roll_no'],
                'name' => $validated['name'],
                'father_name' => $validated['father_name'],
                'joining_class' => $validated['joining_class'] ?? null,
                'joining_date' => Carbon::createFromFormat('d.m.Y', $validated['joining_date'])->format('Y-m-d'),
                'leaving_date' => $validated['leaving_date']
                    ? Carbon::createFromFormat('d.m.Y', $validated['leaving_date'])->format('Y-m-d')
                    : null,
                'prev_school' => $validated['prev_school'],
                'character' => $validated['character'],
                'class' => $validated['class'],
                'stream' => $validated['stream'] ?? null,
                'date_from' => Carbon::createFromFormat('m/d/Y', $validated['date_from'])->format('Y-m-d'),
                'date_to' => $validated['date_to']
                    ? Carbon::createFromFormat('m/d/Y', $validated['date_to'])->format('Y-m-d')
                    : null,
                'dob' => Carbon::createFromFormat('m/d/Y', $validated['dob'])->format('Y-m-d'),
                'dob_words' => Carbon::createFromFormat('Y-m-d', Carbon::createFromFormat('m/d/Y', $validated['dob'])->format('Y-m-d'))->format('F j, Y'),
                'promotion' => $validated['promotion'],
                'status' => 1, // Default status
            ];

            if ($id) {
                // Update existing record
                $record = TransferCertificateModel::findOrFail($id);
                $record->update($data);
            } else {
                // Create a new record
                $record = TransferCertificateModel::create($data);
            }

            return response()->json([
                'message' => $id ? 'Record updated successfully.' : 'Record created successfully.',
                'data' => $record->makeHidden(['id', 'created_at', 'updated_at']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time for large files
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // File validation and path
            $csvFilePath = storage_path('app/public/transfer_certificate.csv');

            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read CSV
            $csv = Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset(0); // First row as headers
            $records = (new Statement())->process($csv);

            $batchSize = 1000; // Number of records to process in one batch
            $data = [];

            // Optionally truncate the table
            TransferCertificateModel::truncate();

            DB::beginTransaction();

            foreach ($records as $row) {
                try {
                    // Map and transform the old columns to match the new table columns
                    $data[] = [
                        'id' => $row['id'] ?? null,
                        'dated' => $row['dated'] ?? now()->toDateString(),
                        'serial_no' => $row['serial_no'] ?? null,
                        'registration_no' => $row['registration_no'] ?? null,
                        'st_id' => isset($row['st_id']) && is_numeric($row['st_id']) ? (int) $row['st_id'] : 0,
                        'st_roll_no' => $row['st_roll_no'] ?? null,
                        'name' => $row['name'] ?? null,
                        'father_name' => $row['father_name'] ?? null,
                        'joining_class' => $row['joining_class'] ?? null,
                        'joining_date' => isset($row['joining_date']) && !empty($row['joining_date']) 
                            ? (\DateTime::createFromFormat('d-m-Y', $row['joining_date']) !== false 
                                ? \DateTime::createFromFormat('d-m-Y', $row['joining_date'])->format('Y-m-d') 
                                : null) 
                            : null,
                        'leaving_date' => isset($row['leaving_date']) && !empty($row['leaving_date']) 
                            ? (\DateTime::createFromFormat('d-m-Y', $row['leaving_date']) !== false 
                                ? \DateTime::createFromFormat('d-m-Y', $row['leaving_date'])->format('Y-m-d') 
                                : null) 
                            : null,
                        'prev_school' => $row['prev_school'] ?? null,
                        'character' => $row['character'] ?? null,
                        'class' => $row['class'] ?? null,
                        'stream' => $row['stream'] ?? null,
                        'date_from' => isset($row['date_from']) && !empty($row['date_from']) 
                            ? (\DateTime::createFromFormat('m/d/Y', $row['date_from']) !== false 
                                ? \DateTime::createFromFormat('m/d/Y', $row['date_from'])->format('Y-m-d') 
                                : null) 
                            : null,
                        'date_to' => isset($row['date_to']) && !empty($row['date_to']) 
                            ? (\DateTime::createFromFormat('m/d/Y', $row['date_to']) !== false 
                                ? \DateTime::createFromFormat('m/d/Y', $row['date_to'])->format('Y-m-d') 
                                : null) 
                            : null,
                        'dob' => isset($row['dob']) && !empty($row['dob']) 
                            ? (\DateTime::createFromFormat('m/d/Y', $row['dob']) !== false 
                                ? \DateTime::createFromFormat('m/d/Y', $row['dob'])->format('Y-m-d') 
                                : null) 
                            : null,
                        'dob_words' => $row['dob_words'] ?? null,
                        'promotion' => $row['promotion'] ?? 'Not Applicable', // Default to 'Not Applicable'
                        'status' => isset($row['status']) && in_array($row['status'], [0, 1]) ? $row['status'] : 1, // Default to 1
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        TransferCertificateModel::insert($data);
                        $data = []; // Reset batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                TransferCertificateModel::insert($data);
            }

            DB::commit();

            return response()->json(['message' => 'CSV imported successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }

    // Fetch Records
    public function index(Request $request)
{
    try {
        // Validate the request
        $validated = $request->validate([
            'search' => 'nullable|string|max:255', // Search for name or roll no
            'leaving_date_from' => 'nullable|date', // Leaving date range start
            'leaving_date_to' => 'nullable|date|after_or_equal:leaving_date_from', // Leaving date range end
            'offset' => 'nullable|integer|min:0', // Pagination offset
            'limit' => 'nullable|integer|min:1|max:100', // Pagination limit
        ]);

        $offset = $validated['offset'] ?? 0;
        $limit = $validated['limit'] ?? 10;

        // Start the query
        $query = TransferCertificateModel::query();

        // Apply search filter (name or roll number)
        if (!empty($validated['search'])) {
            $searchTerm = '%' . strtolower($validated['search']) . '%';
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->whereRaw('LOWER(name) like ?', [$searchTerm])
                    ->orWhereRaw('LOWER(st_roll_no) like ?', [$searchTerm]);
            });
        }

        // Apply leaving date filters
        if (!empty($validated['leaving_date_from']) || !empty($validated['leaving_date_to'])) {
            $query->where(function ($subQuery) use ($validated) {
                if (!empty($validated['leaving_date_from'])) {
                    $subQuery->where('leaving_date', '>=', $validated['leaving_date_from']);
                }
                if (!empty($validated['leaving_date_to'])) {
                    $subQuery->where('leaving_date', '<=', $validated['leaving_date_to']);
                }
            });
        }

        // Get the total count of records for pagination
        $totalCount = $query->count();

        // Fetch paginated results
        $records = $query->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get(['id', 'name','st_roll_no','joining_date', 'leaving_date']); // Select only required fields

        // Response
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Transfer certificates fetched successfully.',
             // Static value or replace with dynamic if applicable
            'data' => $records,
            'total' => $totalCount, // Include the total count for pagination
        ]);
    } catch (\Exception $e) {
        // Handle exceptions
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching records.',
            'error' => $e->getMessage(),
        ]);
    }
}
public function getStudentDetails(Request $request)
{
    // Validate the input
    $validated = $request->validate([
        'st_roll_no' => 'required|string|max:255', // Student roll number
    ]);

    try {
        // Fetch the student details from t_students table
        $student = DB::table('t_students')
            ->select('id', 'st_first_name', 'st_last_name', 'st_dob', 'st_admitted', 'st_admitted_class', 'st_roll_no')
            ->where('st_roll_no', $validated['st_roll_no'])
            ->first();

        // Check if student exists
        if (!$student) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        // Fetch the father's name from t_student_details table
        $fatherName = DB::table('t_student_details')
            ->where('st_id', $student->id)
            ->value('f_name');

        // Format the response data
        $data = [
            'id' => $student->id,
            'name' => trim($student->st_first_name . ' ' . $student->st_last_name),
            'st_dob' => $student->st_dob,
            'st_admitted' => $student->st_admitted,
            'st_admitted_class' => $student->st_admitted_class,
            'fathers_name' => $fatherName ?? 'N/A', // Return 'N/A' if father's name is not found
        ];

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Student details fetched successfully.',
            'data' => $data,
        ]);
    } catch (\Exception $e) {
        // Handle exceptions
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching the student details.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
