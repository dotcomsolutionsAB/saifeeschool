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
            // Validate request
            $validated = $request->validate([
                'cg_id' => 'required|integer|exists:t_class_groups,id',
                'subj_name' => 'required|string|max:255', // Aggregate subject name
                'subj_ids' => 'required|array|min:1', // List of subjects to aggregate
                'subj_ids.*' => 'integer|exists:t_subjectFM,subj_id',
            ]);
    
            $cg_id = $validated['cg_id'];
            $subj_name = $validated['subj_name'];
            $subj_ids = $validated['subj_ids']; // Array of subject IDs
            $subj_ids_str = implode(',', $subj_ids);
    
            // **Ensure all subjects belong to the same academic year**
            $academicYearIds = SubjectFMModel::whereIn('subj_id', $subj_ids)
                ->where('cg_id', $cg_id)
                ->pluck('ay_id')
                ->unique();
    
            if ($academicYearIds->count() !== 1) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Subjects must belong to the same academic year for aggregation.',
                ], 400);
            }
    
            $ay_id = $academicYearIds->first(); // Get the common ay_id
    
            // **Find all terms where the subjects exist**
            $termIds = SubjectFMModel::whereIn('subj_id', $subj_ids)
                ->where('cg_id', $cg_id)
                ->pluck('term_id')
                ->unique();
    
            if ($termIds->isEmpty()) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Subjects must belong to at least one term.',
                ], 400);
            }
    
            $createdAggregates = [];
    
            foreach ($termIds as $term_id) {
                // **Check if an aggregate already exists for this term in the same class**
                $existingAggregate = SubjectFMModel::where([
                    'cg_id' => $cg_id,
                    'term_id' => $term_id,
                    'subj_name' => $subj_name,
                ])->exists();
    
                if ($existingAggregate) {
                    return response()->json([
                        'code' => 400,
                        'status' => false,
                        'message' => "An aggregate subject with this name already exists for this class and term (Term ID: $term_id).",
                    ], 400);
                }
    
                // **Ensure none of the subjects are already part of another aggregate**
                $conflictingAggregates = SubjectFMModel::where('cg_id', $cg_id)
                    ->where('term_id', $term_id)
                    ->where(function ($query) use ($subj_ids) {
                        foreach ($subj_ids as $subj_id) {
                            $query->orWhereRaw("FIND_IN_SET(?, subj_init)", [$subj_id]);
                        }
                    })
                    ->exists();
    
                if ($conflictingAggregates) {
                    return response()->json([
                        'code' => 400,
                        'status' => false,
                        'message' => "One or more selected subjects are already part of another aggregate in this class for Term ID: $term_id.",
                    ], 400);
                }
    
                // **Calculate average marks from aggregated subjects**
                $subjectData = SubjectFMModel::whereIn('subj_id', $subj_ids)
                    ->where('cg_id', $cg_id)
                    ->where('term_id', $term_id)
                    ->selectRaw('
                        COALESCE(SUM(theory), 0) as total_theory,
                        COALESCE(SUM(oral), 0) as total_oral,
                        COALESCE(SUM(prac), 0) as total_prac,
                        COUNT(id) as total_subjects
                    ')
                    ->first();
    
                if (!$subjectData || $subjectData->total_subjects == 0) {
                    return response()->json([
                        'code' => 400,
                        'status' => false,
                        'message' => "Invalid subjects provided for aggregation in Term ID: $term_id.",
                    ], 400);
                }
    
                // **Calculate Average Marks**
                $average_theory = round($subjectData->total_theory / $subjectData->total_subjects, 2);
                $average_oral = round($subjectData->total_oral / $subjectData->total_subjects, 2);
                $average_prac = round($subjectData->total_prac / $subjectData->total_subjects, 2);
                $total_marks = $average_theory + $average_oral + $average_prac;
    
                // **Create new entry in `t_subjectFM`**
                $aggregateSubject = SubjectFMModel::create([
                    'subj_id' => null, // No subject ID since it's an aggregate
                    'subj_name' => $subj_name,
                    'subj_init' => $subj_ids_str, // Store aggregated subject IDs
                    'cg_id' => $cg_id,
                    'term_id' => $term_id,
                    'ay_id' => $ay_id,
                    'type' => 'A', // Aggregate Type
                    'theory' => $average_theory,
                    'oral' => $average_oral,
                    'prac' => $average_prac,
                    'marks' => $total_marks,
                ]);
    
                $createdAggregates[] = $aggregateSubject;
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Aggregate subject created successfully for all relevant terms!',
                'data' => $createdAggregates,
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
