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
use App\Models\StudentClassModel;
use App\Models\StudentDetailsModel;
use Illuminate\Support\Facades\Storage; 
use Mpdf\Mpdf; 


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
            'st_roll_no' => 'required|string|max:100', // Student roll number
            'name' => 'required|string|max:512', // Student name
            'joining_date' => 'required|date', // Joining date
            'leaving_date' => 'nullable|date', // Leaving date (optional)
            'stream' => 'required|string|max:100', // Stream
            'date_from' => 'required|string|max:100', // Date from
            'dob' => 'required|date', // Date of birth
        ]);
    
        try {
            // Check if a character certificate already exists for this roll number (to prevent duplicates)
            $existingCertificate = CharacterCertificateModel::where('st_roll_no', $validated['st_roll_no'])->first();
    
            if (!$id && $existingCertificate) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'A Character Certificate already exists for this roll number.',
                ], 400);
            }
    
            // Find the student with the given roll number
            $student = StudentModel::where('st_roll_no', $validated['st_roll_no'])->first();
    
            if (!$student) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'No student found with the provided roll number.'
                ], 404);
            }
    
            // Fetch last serial number and increment by 1 for a new record
            if (!$id) {
                $lastSerialNo = CharacterCertificateModel::orderBy('serial_no', 'desc')->value('serial_no') ?? 0;
                $serialNo = $lastSerialNo + 1;
            } else {
                // If updating, keep the existing serial_no
                $serialNo = CharacterCertificateModel::where('id', $id)->value('serial_no');
            }
    
            // Prepare the data to be inserted or updated
            $data = [
                'dated' => now()->toDateString(), // Current date
                'serial_no' => $serialNo, // Auto-incremented for new records
                'registration_no' => $validated['st_roll_no'], // Registration number is same as roll number
                'st_id' => $student->id, // Student ID from database
                'st_roll_no' => $validated['st_roll_no'], // Student roll number
                'name' => $validated['name'], // Student name
                'joining_date' => Carbon::createFromFormat('Y-m-d', $validated['joining_date'])->format('Y-m-d'),
                'leaving_date' => $validated['leaving_date']
                    ? Carbon::createFromFormat('Y-m-d', $validated['leaving_date'])->format('Y-m-d')
                    : null,
                'stream' => $validated['stream'], // Stream
                'date_from' => $validated['date_from'], // Date from
                'dob' => $validated['dob'], // Date of birth
                'dob_words' => Carbon::createFromFormat('Y-m-d', $validated['dob'])->format('F j, Y'), // Convert DOB to words
            ];
    
            if ($id) {
                // Update existing record
                $record = CharacterCertificateModel::findOrFail($id);
                $record->update($data);
                $message = 'Record updated successfully.';
            } else {
                // Create new record
                $record = CharacterCertificateModel::create($data);
                $message = 'Record created successfully.';
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => $message,
                'data' => $record->makeHidden(['created_at', 'updated_at']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to process record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Fetch Records
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'search' => 'nullable|string|max:255', // Search for name or roll no
                'date_from' => 'nullable|date', // Start date range
                'date_to' => 'nullable|date|after_or_equal:date_from', // End date range
                'offset' => 'nullable|integer|min:0', // Pagination offset
                'limit' => 'nullable|integer|min:1|max:100', // Pagination limit
            ]);
    
            $offset = $validated['offset'] ?? 0;
            $limit = $validated['limit'] ?? 10;
    
            // Start the query
            $query = CharacterCertificateModel::query();
    
            // Apply search filter (name or roll number)
            if (!empty($validated['search'])) {
                $searchTerm = '%' . strtolower($validated['search']) . '%';
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->whereRaw('LOWER(name) like ?', [$searchTerm])
                        ->orWhereRaw('LOWER(st_roll_no) like ?', [$searchTerm]);
                });
            }
    
            // Apply date filters
            if (!empty($validated['date_from']) || !empty($validated['date_to'])) {
                $query->where(function ($subQuery) use ($validated) {
                    if (!empty($validated['date_from'])) {
                        $subQuery->where('date', '>=', $validated['date_from']);
                    }
                    if (!empty($validated['date_to'])) {
                        $subQuery->where('date', '<=', $validated['date_to']);
                    }
                });
            }
    
            // Get the total count of records for pagination
            $totalCount = $query->count();
    
            // Fetch paginated results
            $records = $query->orderBy('id', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get(['id','registration_no', 'st_roll_no', 'name', 'leaving_date', 'date_from']); // Select only required fields
    
            // Response
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Character Certificates fetched successfully.',
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

    // Delete Record
    public function destroy($id)
    {
        try {
            // Get the last created record (highest ID)
            $lastRecord = CharacterCertificateModel::orderBy('id', 'desc')->first();
    
            // Check if the provided ID matches the last record's ID
            if (!$lastRecord || $lastRecord->id != $id) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Only the last created record can be deleted.'
                ], 400);
            }
    
            // Delete the last record
            $lastRecord->delete();
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Record deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to delete record.',
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

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'cg_id' => 'required|integer|exists:t_student_classes,cg_id', // Class Group ID
            'leaving_date' => 'required|date_format:d-m-Y', // Leaving date
            'stream' => 'required|string|max:100', // Stream
            'date_from' => 'required|string|max:100', // Date from
        ]);
    
        try {
            // Fetch all students from the given class group (`cg_id`)
            $students = StudentClassModel::where('cg_id', $validated['cg_id'])
                ->with('student')
                ->get()
                ->pluck('student');
    
            if ($students->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'No students found for the given class group.',
                ], 404);
            }
    
            $createdRecords = [];
    
            foreach ($students as $student) {
                // Ensure student data exists
                if (!$student || !$student->st_roll_no) {
                    continue;
                }
    
                // Check if a Character Certificate already exists for the student (to prevent duplicates)
                $existingCertificate = CharacterCertificateModel::where('st_roll_no', $student->st_roll_no)->exists();
                if ($existingCertificate) {
                    continue; // Skip this student if a certificate already exists
                }
    
                // Fetch the last issued certificate's serial number and increment it
                $lastSerialNo = CharacterCertificateModel::orderBy('serial_no', 'desc')->value('serial_no') ?? 0;
                $newSerialNo = $lastSerialNo + 1;
    
                // Prepare data for new Character Certificate
                $data = [
                    'dated' => now()->toDateString(), // Current date
                    'serial_no' => $newSerialNo, // New Serial Number
                    'registration_no' => $student->st_roll_no, // Registration No = Roll No
                    'st_id' => $student->id,
                    'st_roll_no' => $student->st_roll_no,
                    'name' => trim($student->st_first_name . ' ' . $student->st_last_name),
                    'joining_date' => $student->st_admitted ? \Carbon\Carbon::parse($student->st_admitted)->format('Y-m-d') : null,
                    'leaving_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $validated['leaving_date'])->format('Y-m-d'),
                    'stream' => $validated['stream'],
                    'date_from' => $validated['date_from'],
                    'dob' => $student->st_dob,
                    'dob_words' => \Carbon\Carbon::parse($student->st_dob)->format('F j, Y'),
                ];
    
                // Create the Character Certificate record
                $createdRecord = CharacterCertificateModel::create($data);
                $createdRecords[] = $createdRecord->makeHidden(['id', 'created_at', 'updated_at']);
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Character Certificates created successfully.',
                'data' => $createdRecords,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to process bulk Character Certificate creation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDetails(Request $request)
{
    try {
        // Validate the request to ensure 'id' is provided
        $validated = $request->validate([
            'id' => 'required|integer|exists:t_character_certificate,id',
        ]);

        // Fetch the record by ID
        $record = CharacterCertificateModel::findOrFail($validated['id']);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Character certificate fetched successfully.',
            'data' => $record->makeHidden(['created_at', 'updated_at']), // Hide unnecessary fields
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 404,
            'status' => false,
            'message' => 'Character certificate not found.',
            'error' => $e->getMessage(),
        ], 404);
    }
}
public function printPdf($id)
{
    try {
        // Validate request to ensure 'id' is provided
        

        // Fetch the record by ID
        $record = CharacterCertificateModel::findOrFail($id);

        // Prepare data for the PDF template
        $data = [
            'serial_no' => $record->serial_no,
            'date' => \Carbon\Carbon::parse($record->dated)->format('d-m-Y'),
            'roll_no' => $record->st_roll_no,
            'name' => $record->name,
            'registration_no' => $record->registration_no,
            'joining_date' => $record->joining_date ? \Carbon\Carbon::parse($record->joining_date)->format('d-m-Y') : 'N/A',
            'leaving_date' => $record->leaving_date ? \Carbon\Carbon::parse($record->leaving_date)->format('d-m-Y') : 'N/A',
            'stream' => $record->stream ?? 'N/A',
            'date_from' => $record->date_from ?? 'N/A',
            'dob' => $record->dob ? \Carbon\Carbon::parse($record->dob)->format('d-m-Y') : 'N/A',
            'dob_words' => $record->dob_words ?? 'N/A',
        ];

        // Define the file name and directory
        $directory = "exports";
        $fileName = 'CharacterCertificate_' . now()->format('Y_m_d_H_i_s') . '.pdf';
        $fullPath = storage_path("app/public/{$directory}/{$fileName}");

        // Ensure the directory exists
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Generate the PDF using mPDF
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_header' => 10,
            'margin_footer' => 10,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        // Set the title for the PDF
        $mpdf->SetTitle('Character Certificate');

        // Render the HTML template (Make sure you have a Blade file: resources/views/exports/character_certificate.blade.php)
        $html = view('exports.character_certificate', compact('data'))->render();

        // Write HTML to the PDF
        $mpdf->WriteHTML($html);

        // Output the PDF to a file
        $mpdf->Output($fullPath, \Mpdf\Output\Destination::FILE);

        // Return metadata about the file
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'PDF generated successfully.',
            'data' => [
                'file_url' => url('storage/exports/' . $fileName),
                'file_name' => $fileName,
                'file_size' => filesize($fullPath),
                'content_type' => 'application/pdf',
            ],
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'code' => 404,
            'status' => false,
            'message' => 'Record not found.',
            'error' => $e->getMessage(),
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while generating the PDF.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function export(Request $request)
{
    $validated = $request->validate([
        //'type' => 'required|in:excel,pdf', // Type of export (if needed)
        'search' => 'nullable|string|max:255', // Search term for roll number or name
        'date_from' => 'nullable|date', // Start date filter
        'date_to' => 'nullable|date|after_or_equal:date_from', // End date filter
    ]);

    try {
        // Initialize query
        $query = CharacterCertificateModel::query();

        // Apply search filter (Search by roll number or name)
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';
            $query->where('st_roll_no', 'like', $searchTerm)
                ->orWhere('name', 'like', $searchTerm);
        }

        // Apply date filters
        if (!empty($validated['date_from'])) {
            $query->where('dated', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->where('dated', '<=', $validated['date_to']);
        }

        // Order by serial number descending
        $query->orderBy('serial_no', 'desc');

        // Fetch and map data
        $data = $query->get()->map(function ($record) {
            return [
                'SN' => $record->serial_no, // Serial number
                'Date' => \Carbon\Carbon::parse($record->dated)->format('d-m-Y'), // Date of record
                'Roll No' => $record->st_roll_no, // Student roll number
                'Name' => $record->name, // Student name
                'Registration No' => $record->registration_no, // Registration number
                'Joining Date' => $record->joining_date ? \Carbon\Carbon::parse($record->joining_date)->format('d-m-Y') : 'N/A', // Joining date
                'Leaving Date' => $record->leaving_date ? \Carbon\Carbon::parse($record->leaving_date)->format('d-m-Y') : 'N/A', // Leaving date
                'Stream' => $record->stream ?? 'N/A', // Stream
                'Date From' => $record->date_from ?? 'N/A', // Date from
                'DOB' => $record->dob ? \Carbon\Carbon::parse($record->dob)->format('d-m-Y') : 'N/A', // Date of birth
                'DOB (Words)' => $record->dob_words ?? 'N/A', // DOB in words
            ];
        })->toArray();

        if (empty($data)) {
            return response()->json(['message' => 'No data available for export.'], 404);
        }

        // Export as Excel or PDF
        return $this->exportExcel($data);

        // return $this->exportPdf($data); // Uncomment for PDF export if needed

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while exporting data.',
            'error' => $e->getMessage(),
        ]);
    }
}
private function exportExcel(array $data)
{
    // Define the directory and file name
    $directory = "exports";
    $fileName = 'CharacterCertificates_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
    $fullPath = "{$directory}/{$fileName}";

    // Use Maatwebsite Excel to export data
    \Maatwebsite\Excel\Facades\Excel::store(
        new \App\Exports\CharacterExport($data), // Ensure you have this export class
        $fullPath,
        'public'
    );

    // Return metadata about the file
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


}
