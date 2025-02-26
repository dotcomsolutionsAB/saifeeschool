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
use Illuminate\Support\Facades\Storage; // For file storage
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;


use Barryvdh\DomPDF\Facade\Pdf;


class TransferCertificateController extends Controller
{
    //
    public function storeOrUpdate(Request $request, $id = null)
{
    $validated = $request->validate([
        'st_roll_no' => 'required|string|max:255',
        'name' => 'required|string|max:512',
        'father_name' => 'required|string|max:256',
        'joining_class' => 'nullable|string|max:100',
        'joining_date' => 'required|string',
        'leaving_date' => 'nullable|string',
        'prev_school' => 'required|string|max:256',
        'character' => 'required|string|max:100',
        'class' => 'required|string|max:100',
        'stream' => 'nullable|string|max:100',
        'date_from' => 'required|string',
        'date_to' => 'nullable|string',
        'dob' => 'required|string',
        'promotion' => 'required|in:Not Applicable,Refused,Promoted',
    ]);

    try {
        // Validation: Check if TC already exists for the provided roll number
        $existingTC = TransferCertificateModel::where('st_roll_no', $validated['st_roll_no'])
            ->when($id, function ($query, $id) {
                return $query->where('id', '<>', $id); // Exclude the current record in case of update
            })
            ->exists();

        if ($existingTC) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'A Transfer Certificate already exists for the provided roll number.',
            ], 422);
        }

        // Parse dates with error handling
        $joiningDate = $this->parseDate($validated['joining_date'], 'd-m-Y', 'joining_date');
        $leavingDate = $this->parseDate($validated['leaving_date'], 'd-m-Y', 'leaving_date', true);
        $dateFrom = $this->parseDate($validated['date_from'], 'm-d-Y', 'date_from');
        $dateTo = $this->parseDate($validated['date_to'], 'm-d-Y', 'date_to', true);
        $dob = $this->parseDate($validated['dob'], 'Y-m-d', 'dob');

        // Handle serial number logic
        if ($id) {
            // For update, retain the existing serial number
            $serialNo = TransferCertificateModel::where('id', $id)->value('serial_no');
            if (!$serialNo) {
                throw new \Exception('Record not found or invalid serial number.');
            }
        } else {
            // For create, increment the last serial number
            $lastSerial = TransferCertificateModel::orderBy('id', 'desc')->value('serial_no') ?? 0;
            $serialNo = $lastSerial + 1;
        }

        // Prepare data for saving
        $data = [
            'serial_no' => $serialNo,
            'dated' => now()->toDateString(),
            'st_roll_no' => $validated['st_roll_no'],
            'name' => $validated['name'],
            'father_name' => $validated['father_name'],
            'joining_class' => $validated['joining_class'],
            'joining_date' => $joiningDate,
            'leaving_date' => $leavingDate,
            'prev_school' => $validated['prev_school'],
            'character' => $validated['character'],
            'class' => $validated['class'],
            'stream' => $validated['stream'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'dob' => $dob,
            'dob_words' => Carbon::parse($dob)->format('F j, Y'), // Convert DOB to readable words
            'promotion' => $validated['promotion'],
            'status' => 1,
        ];

        if ($id) {
            // Update existing record
            $record = TransferCertificateModel::findOrFail($id);
            $record->update($data);
            $message = 'Record updated successfully.';
        } else {
            // Create a new record
            $record = TransferCertificateModel::create($data);
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
            'message' => 'An error occurred while processing the record.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    
    private function parseDate($date, $format, $fieldName, $nullable = false)
    {
        if ($nullable && is_null($date)) {
            return null;
        }
    
        try {
            return Carbon::createFromFormat($format, $date)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Invalid format for $fieldName. Expected format: $format.");
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
            'father_name' => $fatherName ?? 'N/A', // Return 'N/A' if father's name is not found
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
public function getDetails(Request $request)
{
    try {
        // Validate the request to ensure 'id' is provided
        $validated = $request->validate([
            'id' => 'required|integer|exists:t_transfer_certificate,id',
        ]);

        // Fetch the record by ID
        $record = TransferCertificateModel::findOrFail($validated['id']);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Record fetched successfully.',
            'data' => $record->makeHidden(['created_at', 'updated_at']), // Hide unnecessary fields
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 404,
            'status' => false,
            'message' => 'Record not found.',
            'error' => $e->getMessage(),
        ], 404);
    }
}



public function destroy($id)
{
    try {
        // Get the last created record's ID
        $lastId = TransferCertificateModel::orderBy('id', 'desc')->value('id');

        // Check if the provided ID matches the last created ID
        if ($id != $lastId) {
            return response()->json([
                'code' => 403,
                'status' => false,
                'message' => 'You can only delete the last created record.',
            ], 403);
        }

        // Find and delete the record
        $record = TransferCertificateModel::findOrFail($id);
        $record->delete();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Record deleted successfully.',
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'code' => 404,
            'status' => false,
            'message' => 'Record not found.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while deleting the record.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function export(Request $request)
{
    $validated = $request->validate([
        //'type' => 'required|in:excel,pdf', // Type of export
        'search' => 'nullable|string|max:255', // Search term for roll number or name
        'date_from' => 'nullable|date', // Start date filter
        'date_to' => 'nullable|date|after_or_equal:date_from', // End date filter
    ]);

    try {
        // Initialize query
        $query = TransferCertificateModel::query();

        // Apply search filter
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
        $data = $query->get()->map(function ($record, $index) {
            return [
                'SN' => $record->serial_no, // Serial number
                'Date' => \Carbon\Carbon::parse($record->dated)->format('d-m-Y'), // Date of record
                'Roll No' => $record->st_roll_no, // Student roll number
                'Name' => $record->name, // Student name
                'Father Name' => $record->father_name, // Father's name
                'Joining Class' => $record->joining_class ?? 'N/A', // Joining class
                'Joining Date' => $record->joining_date ? \Carbon\Carbon::parse($record->joining_date)->format('d-m-Y') : 'N/A', // Joining date
                'Leaving Date' => $record->leaving_date ? \Carbon\Carbon::parse($record->leaving_date)->format('d-m-Y') : 'N/A', // Leaving date
                'Previous School' => $record->prev_school ?? 'N/A', // Previous school
                'Character' => $record->character ?? 'N/A', // Character
                'Class' => $record->class ?? 'N/A', // Class
                'Stream' => $record->stream ?? 'N/A', // Stream
                'Date From' => $record->date_from ? \Carbon\Carbon::parse($record->date_from)->format('d-m-Y') : 'N/A', // Date from
                'Date To' => $record->date_to ? \Carbon\Carbon::parse($record->date_to)->format('d-m-Y') : 'N/A', // Date to
                'DOB' => $record->dob ? \Carbon\Carbon::parse($record->dob)->format('d-m-Y') : 'N/A', // Date of birth
                'Promotion' => $record->promotion ?? 'N/A', // Promotion
            ];
        })->toArray();

        if (empty($data)) {
            return response()->json(['message' => 'No data available for export.'], 404);
        }

        // Export as Excel or PDF
       
            return $this->exportExcel($data);
        

       // return $this->exportPdf($data);

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
    $fileName = 'TransferCertificates_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
    $fullPath = "{$directory}/{$fileName}";

    // Use Maatwebsite to export the data
    \Maatwebsite\Excel\Facades\Excel::store(
        new \App\Exports\TransferExport($data), // Replace this with your export class if necessary
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
private function exportPdf(array $data)
{
    // Define the file name
    $directory = "exports";
    $fileName = 'TransferCertificates_' . now()->format('Y_m_d_H_i_s') . '.pdf';
    $fullPath = storage_path("app/public/{$directory}/{$fileName}");

    // Ensure directory exists
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

    $mpdf->SetTitle('Transfer Certificate Export');
    $html = view('exports.transfer_certificates', compact('data'))->render(); // Ensure you have this Blade file

    $mpdf->WriteHTML($html);
    $mpdf->Output($fullPath, \Mpdf\Output\Destination::FILE);

    // Return metadata about the file
    return response()->json([
        'code' => 200,
        'status' => true,
        'message' => 'File available for download.',
        'data' => [
            'file_url' => url('storage/exports/' . $fileName),
            'file_name' => $fileName,
            'file_size' => filesize($fullPath),
            'content_type' => 'application/pdf',
        ],
    ]);
}


public function printPdf($id)
{
    try {
        // Fetch the record by ID
        $record = TransferCertificateModel::findOrFail($id);

        // Prepare data for the PDF template
        $data = [
            'serial_no' => $record->serial_no,
            'registration_no' => $record->registration_no ?? 'N/A',
            'dated' => $record->dated ? Carbon::parse($record->dated)->format('d-m-Y') : 'N/A',
            'roll_no' => $record->st_roll_no,
            'name' => $record->name ?? 'N/A',
            'father_name' => $record->father_name ?? 'N/A',
            'joining_class' => $record->joining_class ?? 'N/A',
            'joining_date' => $record->joining_date ? Carbon::parse($record->joining_date)->format('d-m-Y') : 'N/A',
            'leaving_date' => $record->leaving_date ? Carbon::parse($record->leaving_date)->format('d-m-Y') : 'N/A',
            'prev_school' => $record->prev_school ?? 'N/A',
            'character' => $record->character ?? 'N/A',
            'class' => $record->class ?? 'N/A',
            'stream' => $record->stream ?? 'N/A',
            'date_from' => $record->date_from ? Carbon::parse($record->date_from)->format('d-m-Y') : 'N/A',
            'date_to' => $record->date_to ? Carbon::parse($record->date_to)->format('d-m-Y') : 'N/A',
            'dob' => $record->dob ? Carbon::parse($record->dob)->format('d-m-Y') : 'N/A',
            'dob_words' => $this->convertDateToWords($record->dob),
            'promotion' => strtoupper($record->promotion ?? 'N/A'),
            'status' => $record->status == '0' ? 'ORIGINAL' : 'DUPLICATE',
        ];

        // Load Blade View and Generate PDF
        $pdf = Pdf::loadView('pdf.transfer_certificate', $data)->setPaper('a4', 'portrait');

        // Define storage path in public storage
        $directory = "exports"; // Stores in `storage/app/public/exports/`
        $fileName = 'TransferCertificate_' . now()->format('Y_m_d_H_i_s') . '.pdf';
        $fullPath = storage_path("app/public/{$directory}/{$fileName}");

        // Ensure the directory exists in storage
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Store the PDF in storage
        Storage::put($fullPath, $pdf->output());

        // Get the full public URL
        $fullUrl = URL::to(Storage::url($fullPath));

        // Return metadata about the file
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'PDF generated successfully.',
            'data' => [
                'file_url' => $fullUrl, // Full public URL
                'file_name' => $fileName,
                'file_size' => Storage::size($fullPath),
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
public function convertDateToWords($dateString)
{
    if (!$dateString) return 'N/A';

    $timestamp = strtotime($dateString);
    $day = date('j', $timestamp);
    $month = date('F', $timestamp);
    $year = date('Y', $timestamp);

    return strtoupper("$day DAY OF $month, $year");
}
}