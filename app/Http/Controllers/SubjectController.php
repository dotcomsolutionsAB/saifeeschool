<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\SubjectModel;
use App\Models\SubjectFMModel;

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
                        'id' => $row['sub_id'],
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

    // Fetch Records
    public function index(Request $request, $id = null)
    {
        try {
            if ($id) {
                // Fetch the record by ID
                $subjectRecord = SubjectModel::find($id);

                if (!$subjectRecord) {
                    return response()->json([
                        'message' => 'Record not found.',
                        'status' => 'error',
                    ], 404);
                }

                return response()->json([
                    'message' => 'Record fetched successfully.',
                    'data' => $subjectRecord->makeHidden(['created_at', 'updated_at']),
                    'status' => 'success',
                ], 200);
            }

            // Fetch all records
            $subjectRecords = SubjectModel::orderBy('id')->get();

            return response()->json([
                'message' => 'Records fetched successfully.',
                'data' => array_slice($subjectRecords->makeHidden(['created_at', 'updated_at'])->toArray(), 0, 10), // Adjust limit as needed
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching records.',
                'error' => $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }
    public function createAggregateSubject(Request $request)
{
    try {
        // Validate the request
        $validated = $request->validate([
            'cg_id' => 'required|integer|exists:t_class_groups,id',
            'subject' => 'required|string|max:255', // Aggregate subject name
            'subj_ids' => 'required|array|min:1', // List of subjects to aggregate
            'subj_ids.*' => 'integer|exists:t_subjects,id',
        ]);

        $cg_id = $validated['cg_id'];
        $subjectName = $validated['subject'];
        $subj_ids = $validated['subj_ids']; // Array of subject IDs

        // Convert subject IDs to a comma-separated string
        $subj_ids_str = implode(',', $subj_ids);

        // **Check if the same subjects are already in another aggregate**
        $existingAggregate = SubjectModel::where('cg_group', $cg_id)
            ->where('type', 'A') // Aggregate Subject
            ->where(function ($query) use ($subj_ids) {
                foreach ($subj_ids as $subj_id) {
                    $query->orWhereRaw("FIND_IN_SET(?, category)", [$subj_id]);
                }
            })
            ->first();

        if ($existingAggregate) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'One or more selected subjects are already part of another aggregate in this class.',
            ], 400);
        }

        // **Calculate average marks from aggregated subjects**
        $subjectData = SubjectModel::whereIn('id', $subj_ids)
            ->selectRaw('SUM(marks) as total_marks, SUM(prac) as total_prac, COUNT(id) as total_subjects')
            ->first();

        if (!$subjectData || $subjectData->total_subjects == 0) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'Invalid subjects provided.',
            ], 400);
        }

        // Calculate average marks
        $average_marks = round($subjectData->total_marks / $subjectData->total_subjects, 2);
        $average_prac = round($subjectData->total_prac / $subjectData->total_subjects, 2);

        // **Create new subject entry in `t_subjects`**
        $newSubject = SubjectModel::create([
            'subject' => $subjectName,
            'cg_group' => $cg_id,
            'type' => 'A', // Aggregate Subject
            'marks' => $average_marks,
            'prac' => $average_prac,
            'category' => $subj_ids_str, // Store aggregated subject IDs
        ]);

        // **Fetch the latest term ID for this class**
        $latestTerm = DB::table('t_terms')
            ->where('cg_id', $cg_id)
            ->orderBy('id', 'desc')
            ->value('id');

        if (!$latestTerm) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'No term found for the given class.',
            ], 400);
        }

        // **Create new entry in `t_subjectFM`**
        SubjectFMModel::create([
            'subj_id' => $newSubject->id,
            'subj_name' => $subjectName,
            'subj_init' => $subj_ids_str, // Store aggregated subject IDs
            'cg_id' => $cg_id,
            'term_id' => $latestTerm,
            'type' => 'A', // Aggregate Type
            'theory' => $average_marks,
            'oral' => null,
            'prac' => $average_prac,
            'marks' => $average_marks + $average_prac,
        ]);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Aggregate subject created successfully!',
            'data' => $newSubject,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while creating the aggregate subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
