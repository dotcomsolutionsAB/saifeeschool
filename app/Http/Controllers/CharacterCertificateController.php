<?php

namespace App\Http\Controllers;
use App\Models\StudentModel;
use App\Models\CharacterCertificateModel;
use Illuminate\Http\Request;
// use NumberFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;

class CharacterCertificateController extends Controller
{
    //
    // Convert DOB to Words
    // private function convertDobToWords($dob)
    // {
    //     $formatter = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
    //     $date = date('d-m-Y', strtotime($dob));
    //     [$day, $month, $year] = explode('-', $date);
    //     $months = [
    //         "01" => "January", "02" => "February", "03" => "March",
    //         "04" => "April", "05" => "May", "06" => "June",
    //         "07" => "July", "08" => "August", "09" => "September",
    //         "10" => "October", "11" => "November", "12" => "December"
    //     ];

    //     return ucfirst($formatter->format((int)$day)) . " " . $months[$month] . " " . ucfirst($formatter->format((int)$year));
    // }

    // Create or Update Record
    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            'registration_no' => 'required|string|max:100',
            'name' => 'required|string|max:512',
            'joining_date' => 'required|date',
            'leaving_date' => 'nullable|date',
            'stream' => 'required|string|max:100',
            'date_from' => 'required|string|max:100',
            'dob' => 'required|date',
        ]);

        try {
            // Fetch student details
            // $student = StudentModel::where('name', $validated['name'])->first();
            // Find all matching students by concatenating `st_first_name` and `st_last_name`
            $students = StudentModel::whereRaw("CONCAT(st_first_name, ' ', st_last_name) = ?", [$validated['name']])->get();

            if ($students->isEmpty()) {
                return response()->json(['message' => 'No student found with the provided name.'], 404);
            }

             // If multiple students found, filter by `st_flag = 1`
            $student = $students->count() > 1
            ? $students->where('st_flag', 1)->first()
            : $students->first();

            if (!$student) {
                return response()->json(['message' => 'No flagged student found.'], 404);
            }

            // Call the CounterController increment function for serial number
            $counterRequest = new Request(['t_name' => 't_character_certificate']);
            $counterController = new CounterController();
            $incrementResponse = $counterController->increment($counterRequest);

            if ($incrementResponse->getStatusCode() !== 200) {
                return response()->json(['message' => 'Failed to increment serial number.'], 500);
            }

            $serialNo = $incrementResponse->getData()->data->number;

            $data = [
                'dated' => now()->toDateString(), // Current date
                // 'serial_no' => $validated['serial_no'] ?? CharacterCertificateModel::max('serial_no') + 1,
                'serial_no' => $serialNo,
                'registration_no' => $validated['registration_no'],
                'st_id' => $student->id,
                'st_roll_no' => $student->st_roll_no,
                'name' => $validated['name'],
                'joining_date' => Carbon::createFromFormat('d-m-Y', $validated['joining_date'])->format('Y-m-d'),
                'leaving_date' => $validated['leaving_date']
                    ? Carbon::createFromFormat('d-m-Y', $validated['leaving_date'])->format('Y-m-d')
                    : null,
                'stream' => $validated['stream'],
                'date_from' => $validated['date_from'],
                'dob' => $validated['dob'], // Already in Y-m-d format
                // 'dob_words' => $this->convertDobToWords($validated['dob']),
                'dob_words' => Carbon::createFromFormat('Y-m-d', $validated['dob'])->format('F j, Y')
            ];

            if ($id) {
                // Update existing record and retrieve the updated instance
                $record = CharacterCertificateModel::findOrFail($id);
                $record->update($data);
            } else {
                // Create new record
                $record = CharacterCertificateModel::create($data);
            }
    
            return response()->json([
                'message' => $id ? 'Record updated successfully.' : 'Record created successfully.',
                'data' => $record->makeHidden(['id', 'created_at', 'updated_at']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to process record.', 'error' => $e->getMessage()], 500);
        }
    }

    // Fetch Records
    public function index()
    {
        // $records = CharacterCertificateModel::($request->perPage ?? 10);
        $records = CharacterCertificateModel::orderBy('id')->get();

        return response()->json([
            'message' => 'Records fetched successfully.',
            // 'data' => $records->makeHidden(['id', 'created_at', 'updated_at']),
            'data' => array_slice($records->makeHidden(['id', 'created_at', 'updated_at'])->toArray(), 0, 3),
            // 'count' => count($records),
        ]);
    }

    // Delete Record
    public function destroy($id)
    {
        try {
            $record = CharacterCertificateModel::findOrFail($id);
            $record->delete();

            return response()->json(['message' => 'Record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete record.', 'error' => $e->getMessage()], 500);
        }
    }

    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time for large files
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // File validation and path
            // $csvFilePath = $request->file('csv_file')->getRealPath();
            $csvFilePath = storage_path('app/public/character_certificate.csv');

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
            CharacterCertificateModel::truncate();

            DB::beginTransaction();

            foreach ($records as $row) {
                try {
                    // Validate and map the data from old system to new system columns

                    $data[] = [
                        'id' => $row['id'] ?? null,
                        'dated' => $row['dated'] ?? null,
                        'serial_no' => $row['serial_no'] ?? null,
                        'registration_no' => $row['registration_no'] ?? null,
                        'st_id' => isset($row['st_id']) && is_numeric($row['st_id']) ? (int) $row['st_id'] : 0, // Ensure st_id is valid
                        'st_roll_no' => $row['st_roll_no'] ?? null,
                        'name' => $row['name'] ?? null,
                        // 'joining_date' => $row['joining_date'] ?? null,
                        // 'leaving_date' => $row['leaving_date'] ?? null,
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
                        'stream' => $row['stream'] ?? null,
                        'date_from' => $row['date_from'] ?? null,
                        'dob' => $row['dob'] ?? null,
                        'dob_words' => $row['dob_words'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        CharacterCertificateModel::insert($data);
                        $data = []; // Reset batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                CharacterCertificateModel::insert($data);
            }

            DB::commit();

            return response()->json(['message' => 'CSV imported successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'records' => 'required|array',
            'records.*.registration_no' => 'required|string|max:100',
            'records.*.name' => 'required|string|max:512',
            'records.*.joining_date' => 'required|date_format:d-m-Y',
            'records.*.leaving_date' => 'nullable|date_format:d-m-Y',
            'records.*.stream' => 'required|string|max:100',
            'records.*.date_from' => 'required|string|max:100',
            'records.*.dob' => 'required|date_format:Y-m-d',
        ]);

        try {
            $recordsData = $validated['records'];
            $createdRecords = [];

            foreach ($recordsData as $record) {
                // Fetch student details by name
                $students = StudentModel::whereRaw("CONCAT(st_first_name, ' ', st_last_name) = ?", [$record['name']])->get();

                if ($students->isEmpty()) {
                    throw new \Exception("No student found with the provided name: " . $record['name']);
                }

                // If multiple students found, filter by `st_flag = 1`
                $student = $students->count() > 1
                    ? $students->where('st_flag', 1)->first()
                    : $students->first();

                if (!$student) {
                    throw new \Exception("No flagged student found for name: " . $record['name']);
                }

                // Call the CounterController increment function for serial number
                $counterRequest = new Request(['t_name' => 't_character_certificate']);
                $counterController = new CounterController();
                $incrementResponse = $counterController->increment($counterRequest);

                if ($incrementResponse->getStatusCode() !== 200) {
                    throw new \Exception("Failed to increment serial number.");
                }

                $serialNo = $incrementResponse->getData()->data->number;

                $data = [
                    'dated' => now()->toDateString(), // Current date
                    'serial_no' => $serialNo, // Incremented serial number
                    'registration_no' => $record['registration_no'],
                    'st_id' => $student->id,
                    'st_roll_no' => $student->st_roll_no,
                    'name' => $record['name'],
                    'joining_date' => Carbon::createFromFormat('d-m-Y', $record['joining_date'])->format('Y-m-d'),
                    'leaving_date' => isset($record['leaving_date']) && $record['leaving_date']
                        ? Carbon::createFromFormat('d-m-Y', $record['leaving_date'])->format('Y-m-d')
                        : null,
                    'stream' => $record['stream'],
                    'date_from' => $record['date_from'],
                    'dob' => $record['dob'],
                    'dob_words' => Carbon::createFromFormat('Y-m-d', $record['dob'])->format('F j, Y'),
                ];

                $createdRecord = CharacterCertificateModel::create($data);
                $createdRecords[] = $createdRecord->makeHidden(['id', 'created_at', 'updated_at']);
            }

            return response()->json([
                'message' => 'Records created successfully.',
                'data' => $createdRecords,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process bulk creation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
