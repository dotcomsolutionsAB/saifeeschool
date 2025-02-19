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
    public function registerandUpdate(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'class_name' => 'required|string|exists:t_class_groups,cg_name', // Ensure the class name exists in the class table
            'subj_id' => 'required|integer|exists:t_subjects,id', // Ensure the subject ID exists
            // 'marks' => 'required|numeric|min:0', // Marks scored
            'marks' => 'required', 
            'prac' => 'nullable|numeric|min:0', // Practical marks
            'serialNo' => 'nullable|string|max:100', // Serial Number
            'st_roll_no' => 'required|numeric|exists:t_students,st_roll_no', // Student roll number
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

            // Check if marks already exist for the same st_roll_no, session, and subj_id
            $existingMarks = MarksModel::where('st_roll_no', $validated['st_roll_no'])
                ->where('ay_id', $academicYear->id) // Match academic year
                ->where('subj_id', $validated['subj_id'])
                ->first();

            if ($existingMarks) {
                // Update the existing record
                $existingMarks->update([
                    'cg_id' => $classGroup->id,           // Class group ID
                    'term' => 1,                          // Term is set to 1
                    'unit' => 1,                          // Unit is set to 1
                    'marks' => $validated['marks'],       // Marks scored
                    'prac' => $validated['prac'] ?? null, // Practical marks (optional)
                    'serialNo' => $validated['serialNo'] ?? null, // Serial Number (optional)
                ]);

                return response()->json([
                    'message' => 'Marks record updated successfully.',
                    'data' => $existingMarks->makeHidden(['id', 'created_at', 'updated_at']),
                ], 200);
            } else {
                // Create a new marks record
                $marks = MarksModel::create([
                    'ay_id' => $academicYear->id,       // Session year from academic year
                    'st_roll_no' => $validated['st_roll_no'], // Student roll number
                    'subj_id' => $validated['subj_id'],  // Subject ID
                    'cg_id' => $classGroup->id,           // Class group ID
                    'term' => 1,                          // Term is set to 1
                    'unit' => 1,                          // Unit is set to 1
                    'marks' => $validated['marks'],       // Marks scored
                    'prac' => $validated['prac'] ?? null, // Practical marks (optional)
                    'serialNo' => $validated['serialNo'] ?? null, // Serial Number (optional)
                ]);

                return response()->json([
                    'message' => 'Marks record created successfully.',
                    'data' => $marks->makeHidden(['id', 'created_at', 'updated_at']),
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
        ini_set('max_execution_time', 300); // Extend execution time for large files
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // File validation and path
            // $csvFilePath = $request->file('csv_file')->getRealPath();
            $csvFilePath = storage_path('app/public/studMarks.csv');

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
            MarksModel::truncate();

            DB::beginTransaction();

            foreach ($records as $row) {
                try {
                    // Validate and prepare the data
                    $data[] = [
                        'id' => $row['id'] ?? null,
                        'ay_id' => $row['session'] ?? null,
                        'st_roll_no' => $row['st_roll_no'] ?? null,
                        'subj_id' => $row['subj_id'] ?? null,
                        'cg_id' => $row['cg_id'] ?? null,
                        'term' => $row['term'] ?? null,
                        'unit' => $row['unit'] ?? null,
                        'marks' => $row['marks'] ?? null,
                        'prac' => $row['prac'] ?? null,
                        'serialNo' => $row['serialNo'] ?? null,
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        MarksModel::insert($data);
                        $data = []; // Reset batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                MarksModel::insert($data);
            }

            DB::commit();

            return response()->json(['message' => 'CSV imported successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getMarksData(Request $request)
    {
        try {
            // Validate the request parameters
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'term_id' => 'required|integer|exists:t_terms,id',
                'cg_id' => 'required|integer|exists:t_class_groups,id',
            ]);
    
            $ay_id = $validated['ay_id'];
            $term_id = $validated['term_id'];
            $cg_id = $validated['cg_id'];
    
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
                ->where('sfm.term_id', $term_id)
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
                    'marks' => $subj->theory,
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
                ->join('t_students as stu', 'm.st_roll_no', '=', 'stu.st_roll_no')
                ->join('t_subjects as subj', 'm.subj_id', '=', 'subj.id')
                ->where('m.cg_id', $cg_id)
                ->where('m.term', $term_id)
                ->selectRaw("
                    stu.id AS st_id,
                    m.cg_id,
                    m.term AS term_id,
                    m.st_roll_no AS roll_no,
                    m.subj_id AS subject_id,
                    m.marks AS theory_marks,
                    m.prac AS prac_marks
                ")
                ->orderBy('stu.st_first_name') // Sorting students alphabetically
                ->get();
    
            // ✅ Process marks - Separate theory & practical in the new format
            $marks = [];
            foreach ($marksRaw as $mark) {
                if (!is_null($mark->theory_marks)) {
                    $marks[] = [
                        'id' => "{$mark->st_id}-{$mark->cg_id}-{$mark->term_id}-{$mark->subject_id}-T",
                        'marks' => $mark->theory_marks
                    ];
                }
    
                if (!is_null($mark->prac_marks) && $mark->prac_marks > 0) {
                    $marks[] = [
                        'id' => "{$mark->st_id}-{$mark->cg_id}-{$mark->term_id}-{$mark->subject_id}-P",
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

    public function createOrUpdateMarks(Request $request)
    {
        try {
            // ✅ Validate the request data
            $validated = $request->validate([
                'id' => 'required|string', // Special format (st_id-cg_id-term_id-subject_id-category)
                'marks' => 'required', // Numeric or letter-based, validated below
            ]);
    
            DB::beginTransaction();
    
            // ✅ Extract values from `id`
            $idParts = explode('-', $validated['id']);
            if (count($idParts) !== 5) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Invalid ID format. Expected format: st_id-cg_id-term_id-subject_id-T/P.",
                ], 400);
            }
    
            [$st_id, $cg_id, $term_id, $subject_id, $category] = $idParts;
    
            // ✅ Validate category (Theory: T, Practical: P)
            if (!in_array($category, ['T', 'P'])) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Invalid category. Must be 'T' (Theory) or 'P' (Practical).",
                ], 400);
            }
    
            // ✅ Fetch student roll number from `t_students`
            $student = DB::table('t_students')->where('id', $st_id)->select('st_roll_no')->first();
            if (!$student) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Invalid student ID: $st_id",
                ], 400);
            }
            $roll_no = $student->st_roll_no;
    
            // ✅ Fetch subject details from `t_subjectFM`
            $subjectFM = DB::table('t_subjectFM')
                ->join('t_subjects', 't_subjectFM.subj_id', '=', 't_subjects.id')
                ->where([
                    't_subjectFM.subj_id' => $subject_id,
                    't_subjectFM.cg_id' => $cg_id,
                    't_subjectFM.term_id' => $term_id
                ])
                ->select('t_subjectFM.theory', 't_subjectFM.prac', 't_subjects.type')
                ->first();
    
            if (!$subjectFM) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "The subject is not assigned to this class and term.",
                ], 400);
            }
    
            // ✅ Validate marks based on type
            if ($subjectFM->type === 'M' && (!is_numeric($validated['marks']) || floor($validated['marks']) != $validated['marks'])) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "For type 'M', marks must be an integer.",
                ], 400);
            }
    
            if ($subjectFM->type === 'G' && !preg_match('/^[A-Za-z]+$/', $validated['marks'])) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "For type 'G', marks must be a letter grade (e.g., A, B, C).",
                ], 400);
            }
    
            // ✅ Ensure marks do not exceed allowed max values
            if ($category === 'T' && (is_null($subjectFM->theory) || $validated['marks'] > $subjectFM->theory)) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Invalid theory marks. Maximum allowed: {$subjectFM->theory}.",
                ], 400);
            }
    
            if ($category === 'P' && (is_null($subjectFM->prac) || $validated['marks'] > $subjectFM->prac)) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Invalid practical marks. Maximum allowed: {$subjectFM->prac}.",
                ], 400);
            }
    
            // ✅ Check if mark entry exists
            $existingMark = DB::table('t_marks')
                ->where([
                    'st_roll_no' => $roll_no,
                    'cg_id' => $cg_id,
                    'term' => $term_id,
                    'subj_id' => $subject_id,
                ])
                ->first();
    
            // ✅ Prepare update data
            $updateData = [
                'marks' => ($category === 'T') ? $validated['marks'] : ($existingMark->marks ?? 0),
                'prac' => ($category === 'P') ? $validated['marks'] : ($existingMark->prac ?? 0),
                'updated_at' => now()
            ];
    
            if ($existingMark) {
                // ✅ Update existing record
                DB::table('t_marks')
                    ->where('id', $existingMark->id)
                    ->update($updateData);
            } else {
                // ✅ Insert new record
                DB::table('t_marks')->insert([
                    'st_roll_no' => $roll_no,
                    'cg_id' => $cg_id,
                    'term' => $term_id,
                    'subj_id' => $subject_id,
                    'marks' => ($category === 'T') ? $validated['marks'] : 0,
                    'prac' => ($category === 'P') ? $validated['marks'] : 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Marks data saved successfully!',
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while saving marks data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateMarksIds()
{
    try {
        // Fetch all marks records that need `st_id` and `marks_id` filled
        $marksRecords = DB::table('t_marks')->get();

        $updatedCount = 0;

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

            // Update the existing record
            DB::table('t_marks')
                ->where('id', $mark->id)
                ->update([
                    'st_id' => $student->id,
                    'marks_id' => $marks_id,
                    'updated_at' => now(),
                ]);

            $updatedCount++;
        }

        return response()->json([
            'message' => "Successfully updated $updatedCount records with marks_id and st_id.",
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update marks_id and st_id.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}