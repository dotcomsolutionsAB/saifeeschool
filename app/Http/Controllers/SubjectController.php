<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\SubjectModel;
use App\Models\SubjectFMModel;
use App\Models\ClassGroupModel;

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
    
            // **Fetch Class Group**
            $classGroup = ClassGroupModel::find($cg_id);
            if (!$classGroup) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Class group not found.',
                ], 400);
            }
    
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
    
            // **Ensure the aggregate subject does not already exist**
            $existingAggregate = SubjectModel::where([
                'subject' => $subj_name,
                'cg_group' => $classGroup->cg_group,
                'type' => 'A' // Aggregate subject
            ])->first();
    
            if ($existingAggregate) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'An aggregate subject with this name already exists in this class group.',
                ], 400);
            }
    
            // **Create new entry in `t_subjects` table** (Only storing ID, Name, and Type)
            $aggregateSubject = SubjectModel::create([
                'subject' => $subj_name,
                'cg_group' => $classGroup->cg_group, // Fetch class group for mapping
                'type' => 'A', // Aggregate subject type
            ]);
    
            $createdAggregates = [];
    
            foreach ($termIds as $term_id) {
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
                $aggregateFM = SubjectFMModel::create([
                    'subj_id' => $aggregateSubject->id, // Reference to the new subject ID
                    'subj_name' => $subj_name,
                    'subj_init' => $subj_ids_str, // Store aggregated subject IDs
                    'cg_id' => $cg_id,
                    'term_id' => $term_id,
                    'type' => 'A', // Aggregate Type
                    'theory' => $average_theory,
                    'oral' => $average_oral,
                    'prac' => $average_prac,
                    'marks' => $total_marks,
                ]);
    
                $createdAggregates[] = $aggregateFM;
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
    public function getSubjectsByClassGroup(Request $request)
{
    try {
        // ✅ Validate the request
        $validated = $request->validate([
            'cg_id' => 'required|integer|exists:t_class_groups,id',
        ]);

        $cg_id = $validated['cg_id'];

        // ✅ Fetch subjects mapped to the given `cg_id`
        $subjects = DB::table('t_subjectFM as sfm')
            ->join('t_subjects as subj', 'sfm.subj_id', '=', 'subj.id')
            ->where('sfm.cg_id', $cg_id)
            ->select('subj.id AS subject_id', 'subj.subject AS subject_name', 'sfm.type')
            ->orderBy('subj.subject')
            ->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Subjects fetched successfully.',
            'data' => $subjects,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching subjects.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function createSubject(Request $request)
{
    try {
        // ✅ Validate request data
        $validated = $request->validate([
            'subject' => 'required|string|max:255|unique:t_subjects,subject',
            'type' => 'required|string|in:M,G,A', // M = Marks, G = Grade, A = Aggregate
        ]);

        // ✅ Create a new subject
        $subject = SubjectModel::create([
            'subject' => $validated['subject'],
            'type' => $validated['type'],
        ]);

        return response()->json([
            'code' => 201,
            'status' => true,
            'message' => 'Subject created successfully.',
            'data' => $subject,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while creating the subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function mapSubjectToClass(Request $request)
{
    try {
        // ✅ Validate request data
        $validated = $request->validate([
            'cg_id' => 'required|integer|exists:t_class_groups,id',
            'subj_id' => 'required|integer|exists:t_subjects,id',
            'theory' => 'nullable|integer|min:0',
            'oral' => 'nullable|integer|min:0',
            'prac' => 'nullable|integer|min:0',
            'marks' => 'nullable|integer|min:0',
        ]);

        // ✅ Fetch subject details
        $subject = SubjectModel::where('id', $validated['subj_id'])->first();
        if (!$subject) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'Invalid subject ID.',
            ], 400);
        }

        $subject_name = $subject->subject;
        $subject_type = $subject->type;

        // ✅ Get all terms for the given class group
        $termIds = DB::table('t_terms')
            ->where('cg_id', $validated['cg_id'])
            ->pluck('id');

        if ($termIds->isEmpty()) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'No terms found for the given class group.',
            ], 400);
        }

        // ✅ Insert the subject for all terms in `t_subjectFM`
        $createdEntries = [];
        foreach ($termIds as $term_id) {
            $exists = SubjectFMModel::where([
                'subj_id' => $validated['subj_id'],
                'cg_id' => $validated['cg_id'],
                'term_id' => $term_id
            ])->exists();

            if (!$exists) {
                $subjectFM = SubjectFMModel::create([
                    'subj_id' => $validated['subj_id'],
                    'subj_name' => $subject_name,
                    'cg_id' => $validated['cg_id'],
                    'term_id' => $term_id,
                    'type' => $subject_type,
                    'theory' => $validated['theory'] ?? null,
                    'oral' => $validated['oral'] ?? null,
                    'prac' => $validated['prac'] ?? null,
                    'marks' => $validated['marks'] ?? null,
                ]);
                $createdEntries[] = $subjectFM;
            }
        }

        return response()->json([
            'code' => 201,
            'status' => true,
            'message' => 'Subject successfully mapped to all terms of the class.',
            'data' => $createdEntries,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while mapping the subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
