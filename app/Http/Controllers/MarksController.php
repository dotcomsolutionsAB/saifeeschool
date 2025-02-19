<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\MarksModel;
use App\Models\AcademicYearModel;
use App\Models\ClassGroupModel;

class MarksController extends Controller
{
    //
    public function registerAndUpdate(Request $request)
    {
        // ✅ Validate input
        $validated = $request->validate([
            'marks_id' => 'required|string', // Must be in format st_id-cg_id-term-subj_id[-P]
            'marks' => 'required|numeric|min:0', // Either theory marks or practical marks
        ]);
    
        try {
            // ✅ Check if marks_id ends with "-P" (Practical marks)
            $isPractical = str_ends_with($validated['marks_id'], '-P');
    
            // ✅ Remove `-P` if it's a practical entry
            $processedMarksId = $isPractical ? substr($validated['marks_id'], 0, -2) : $validated['marks_id'];
    
            // ✅ Extract values from `marks_id`
            $idParts = explode('-', $processedMarksId);
            if (count($idParts) !== 4) {
                return response()->json([
                    'message' => "Invalid marks_id format. Expected: st_id-cg_id-term-subj_id[-P].",
                ], 400);
            }
    
            [$st_id, $cg_id, $term, $subj_id] = $idParts;
    
            // ✅ Fetch student details
            $student = DB::table('t_students')->where('id', $st_id)->select('st_roll_no')->first();
            if (!$student) {
                return response()->json(['message' => 'Invalid student ID.'], 400);
            }
    
            // ✅ Fetch `ay_id` from `t_class_groups` using `cg_id`
            $classGroup = DB::table('t_class_groups')->where('id', $cg_id)->select('ay_id')->first();
            if (!$classGroup) {
                return response()->json(['message' => 'Invalid class group ID.'], 400);
            }
    
            $ay_id = $classGroup->ay_id; // ✅ Use `ay_id` from `t_class_groups`
    
            // ✅ Check if marks already exist for the same `marks_id`
            $existingMarks = MarksModel::where('marks_id', $processedMarksId)->first();
    
            if ($existingMarks) {
                // ✅ Update existing marks
                $existingMarks->update([
                    'marks' => $isPractical ? $existingMarks->marks : $validated['marks'], // Update marks if theory
                    'prac' => $isPractical ? $validated['marks'] : $existingMarks->prac, // Update prac if practical
                    'updated_at' => now(),
                ]);
    
                return response()->json([
                    'message' => 'Marks updated successfully.',
                    'data' => $existingMarks,
                ], 200);
            } else {
                // ✅ Create a new marks record
                $marks = MarksModel::create([
                    'marks_id' => $processedMarksId,
                    'st_id' => $st_id,
                    'ay_id' => $ay_id, // ✅ Use ay_id from t_class_groups
                    'st_roll_no' => $student->st_roll_no,
                    'subj_id' => $subj_id,
                    'cg_id' => $cg_id,
                    'term' => $term,
                    'unit' => 1, // Assuming default unit
                    'marks' => $isPractical ? 0 : $validated['marks'], // Store in marks if not practical
                    'prac' => $isPractical ? $validated['marks'] : 0, // Store in prac if practical
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                return response()->json([
                    'message' => 'Marks record created successfully.',
                    'data' => $marks,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process marks.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 0); // ✅ No time limit
        ini_set('memory_limit', '2048M'); // ✅ Increase memory limit
    
        try {
            $csvFilePath = storage_path('app/public/studMarks.csv');
    
            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }
    
            // Read CSV
            $csv = Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset(0); // First row as headers
            $records = (new Statement())->process($csv);
    
            $batchSize = 100; // ✅ Process in smaller batches
            $data = [];
            $totalInserted = 0;
    
            foreach ($records as $row) {
                try {
                    // ✅ Fetch student ID using `st_roll_no`
                    $student = DB::table('t_students')
                        ->where('st_roll_no', $row['st_roll_no'] ?? null)
                        ->select('id')
                        ->first();
    
                    if (!$student) {
                        Log::warning('Student not found for roll_no: ' . $row['st_roll_no']);
                        continue; // Skip if student does not exist
                    }
    
                    $st_id = $student->id;
    
                    // ✅ Fetch `ay_id` from `t_class_groups` using `cg_id`
                    $classGroup = DB::table('t_class_groups')
                        ->where('id', $row['cg_id'] ?? null)
                        ->select('ay_id')
                        ->first();
    
                    if (!$classGroup) {
                        Log::warning('Class group not found for cg_id: ' . $row['cg_id']);
                        continue; // Skip if class group does not exist
                    }
    
                    $ay_id = $classGroup->ay_id;
    
                    // ✅ Generate unique `marks_id`
                    $marks_id = "{$st_id}-{$row['cg_id']}-{$row['term']}-{$row['subj_id']}";
    
                    // ✅ Check if record already exists
                    $existingMark = DB::table('t_marks')
                        ->where('marks_id', $marks_id)
                        ->first();
    
                    if ($existingMark) {
                        // ✅ Update existing record
                        DB::table('t_marks')
                            ->where('marks_id', $marks_id)
                            ->update([
                                'marks' => $row['marks'] ?? $existingMark->marks,
                                'prac' => $row['prac'] ?? $existingMark->prac,
                                'updated_at' => now(),
                            ]);
                    } else {
                        // ✅ Insert new record
                        $data[] = [
                            'marks_id' => $marks_id,
                            'st_id' => $st_id,
                            'ay_id' => $ay_id,
                            'st_roll_no' => $row['st_roll_no'],
                            'subj_id' => $row['subj_id'],
                            'cg_id' => $row['cg_id'],
                            'term' => $row['term'],
                            'unit' => $row['unit'] ?? 1, // Default to 1 if not provided
                            'marks' => $row['marks'] ?? 0, // Default to 0 if missing
                            'prac' => $row['prac'] ?? 0,   // Default to 0 if missing
                            'serialNo' => $row['serialNo'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
    
                    // ✅ Insert batch when limit is reached
                    if (count($data) >= $batchSize) {
                        DB::table('t_marks')->insert($data);
                        $totalInserted += count($data);
                        $data = []; // Reset batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }
    
            // ✅ Insert remaining records
            if (count($data) > 0) {
                DB::table('t_marks')->insert($data);
                $totalInserted += count($data);
            }
    
            return response()->json([
                'message' => "CSV imported successfully! Total records inserted: $totalInserted",
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }
    public function getMarksData(Request $request)
{
    try {
        // ✅ Validate the request parameters
        $validated = $request->validate([
            'ay_id' => 'required|integer|exists:t_academic_years,id',
            'term' => 'required|integer|exists:t_terms,term', // ✅ Accept term_name instead of term_id
            'cg_id' => 'required|integer|exists:t_class_groups,id',
        ]);

        $ay_id = $validated['ay_id'];
        $cg_id = $validated['cg_id'];

        // ✅ Fetch `term_id` using `term_name`
        $term = DB::table('t_terms')
            ->where('term', $validated['term'])
            ->select('term')
            ->first();

        if (!$term) {
            return response()->json(['message' => 'Invalid term name.'], 400);
        }

        $term= $term->term; // ✅ Now using `term_id` dynamically

        // ✅ Fetch all students in the class group
        $students = DB::table('t_students as stu')
            ->join('t_student_classes as sc', 'stu.id', '=', 'sc.st_id')
            ->where('sc.cg_id', $cg_id)
            ->selectRaw("
                stu.id AS st_id,
                stu.st_roll_no AS roll_no,
                CONCAT(stu.st_first_name, ' ', stu.st_last_name) AS name
            ")
            ->orderBy('stu.st_first_name')
            ->get();

        // ✅ Fetch all subjects for the given class & term
        $subjectsRaw = DB::table('t_subjectFM as sfm')
            ->join('t_subjects as subj', 'sfm.subj_id', '=', 'subj.id')
            ->where('sfm.cg_id', $cg_id)
            ->where('sfm.term', $term)
            ->selectRaw("
                subj.id AS subject_id,
                subj.subj_name AS subject_name,
                sfm.type,
                sfm.prac,
                sfm.marks
            ")
            ->orderBy('subj.serial') // Sorting subjects by serial order
            ->get();

        // ✅ Process subjects - If practical exists, create separate entries
        $subjects = [];
        foreach ($subjectsRaw as $subj) {
            $subjects[] = [
                'subject_id' => $subj->subject_id,
                'subject_name' => $subj->subject_name,
                'type' => $subj->type,
                'marks' => $subj->marks,
                'category' => 'Theory'
            ];

            if (!is_null($subj->prac) && $subj->prac > 0) {
                $subjects[] = [
                    'subject_id' => $subj->subject_id,
                    'subject_name' => $subj->subject_name,
                    'type' => $subj->type,
                    'marks' => $subj->prac,
                    'category' => 'Practical'
                ];
            }
        }

        // ✅ Fetch marks for each student in each subject
        $marksRaw = DB::table('t_marks as m')
            ->join('t_students as stu', 'm.st_id', '=', 'stu.id')
            ->join('t_subjects as subj', 'm.subj_id', '=', 'subj.id')
            ->where('m.cg_id', $cg_id)
            ->where('m.term', $term)
            ->selectRaw("
                m.marks_id,
                stu.id AS st_id,
                m.cg_id,
                m.term AS term,
                m.subj_id AS subject_id,
                m.marks AS theory_marks,
                m.prac AS prac_marks
            ")
            ->orderBy('stu.st_first_name') // Sorting students alphabetically
            ->orderBy('subj.serial')
            ->get();

        // ✅ Process marks - Separate theory & practical in the new format
        $marks = [];
        foreach ($marksRaw as $mark) {
            if (!is_null($mark->theory_marks)) {
                $marks[] = [
                    'id' => "{$mark->marks_id}",
                    'marks' => $mark->theory_marks
                ];
            }

            if (!is_null($mark->prac_marks) && $mark->prac_marks > 0) {
                $marks[] = [
                    'id' => "{$mark->marks_id}-P",
                    'marks' => $mark->prac_marks
                ];
            }
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Marks data fetched successfully!',
            'data' => [
                'subjects' => $subjects,
                'students' => $students,
                'marks' => $marks
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching marks data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

        public function updateMarksIds()
    {
        try {
            $batchSize = 500; // ✅ Process 500 records per batch
            $updatedCount = 0;
    
            do {
                // ✅ Fetch a batch of records where both `st_id` and `marks_id` are NULL
                $marksRecords = DB::table('t_marks')
                    ->whereNull('st_id')
                    ->whereNull('marks_id')
                    ->limit($batchSize)
                    ->get();
    
                if ($marksRecords->isEmpty()) {
                    break; // ✅ No more records to process
                }
    
                // ✅ Prepare bulk update data
                $updates = [];
                foreach ($marksRecords as $mark) {
                    // Fetch student ID from `t_students` using `st_roll_no`
                    $student = DB::table('t_students')
                        ->where('st_roll_no', $mark->st_roll_no)
                        ->select('id')
                        ->first();
    
                    if (!$student) {
                        continue; // Skip if student is not found
                    }
    
                    // Generate marks_id: st_id - cg_id - term - subj_id
                    $marks_id = "{$student->id}-{$mark->cg_id}-{$mark->term}-{$mark->subj_id}";
    
                    // Add to update batch
                    $updates[] = [
                        'id' => $mark->id,
                        'st_id' => $student->id,
                        'marks_id' => $marks_id,
                        'updated_at' => now(),
                    ];
                }
    
                // ✅ Bulk update in one query
                foreach ($updates as $update) {
                    DB::table('t_marks')
                        ->where('id', $update['id'])
                        ->update([
                            'st_id' => $update['st_id'],
                            'marks_id' => $update['marks_id'],
                            'updated_at' => $update['updated_at'],
                        ]);
                }
    
                $updatedCount += count($updates);
            } while (count($updates) >= $batchSize); // ✅ Continue until all records are processed
    
            return response()->json([
                'message' => "Successfully updated $updatedCount records with st_id and marks_id.",
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update st_id and marks_id.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}