<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassGroupModel;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\AcademicYearModel;
use Illuminate\Support\Facades\DB;
use App\Models\TeacherModel;

class ClassGroupController extends Controller
{
    //
    // Create a new Class Group
    public function createOrUpdate(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'cg_id' => 'nullable|integer|exists:t_class_groups,id', // Required only for update
                'ay_id' => 'required|integer|exists:t_academic_years,id',
                'cg_name' => 'required|string|max:255', // Class group name
                'cg_order' => 'required|integer|min:1', // Class order in academic year
                'teacher_id' => 'nullable|integer|exists:t_teachers,id', // Optional teacher ID
            ]);
    
            // Check if a duplicate class group name exists for the same academic year
            $existingClassGroup = ClassGroupModel::where('ay_id', $validated['ay_id'])
                ->where('cg_name', $validated['cg_name'])
                ->when(!empty($validated['cg_id']), function ($query) use ($validated) {
                    return $query->where('id', '!=', $validated['cg_id']);
                })
                ->exists();
    
            if ($existingClassGroup) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'A class group with this name already exists in the given academic year.',
                ], 400);
            }
    
            // Check if `cg_order` is unique within the same academic year
            $existingOrder = ClassGroupModel::where('ay_id', $validated['ay_id'])
                ->where('cg_order', $validated['cg_order'])
                ->when(!empty($validated['cg_id']), function ($query) use ($validated) {
                    return $query->where('id', '!=', $validated['cg_id']);
                })
                ->exists();
    
            if ($existingOrder) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'The class order must be unique within the academic year.',
                ], 400);
            }
    
            // If `cg_id` exists, update the class group
            if (!empty($validated['cg_id'])) {
                $classGroup = ClassGroupModel::find($validated['cg_id']);
                $oldTeacherId = $classGroup->teacher_id; // Get old teacher ID
    
                $classGroup->update([
                    'ay_id' => $validated['ay_id'],
                    'cg_name' => $validated['cg_name'],
                    'cg_order' => $validated['cg_order'],
                    'teacher_id' => $validated['teacher_id'] ?? null, // Can be null
                ]);
    
                // If teacher is changed, update `is_class_teacher`
                if (!empty($validated['teacher_id']) && $validated['teacher_id'] !== $oldTeacherId) {
                    // Set new teacher's `is_class_teacher` to '1'
                    TeacherModel::where('id', $validated['teacher_id'])->update(['is_class_teacher' => '1']);
    
                    // If an old teacher existed, reset their `is_class_teacher` to '0'
                    if (!empty($oldTeacherId)) {
                        TeacherModel::where('id', $oldTeacherId)->update(['is_class_teacher' => '0']);
                    }
                }
    
                $message = 'Class group updated successfully!';
            } else {
                // Create a new class group
                $classGroup = ClassGroupModel::create([
                    'ay_id' => $validated['ay_id'],
                    'cg_name' => $validated['cg_name'],
                    'cg_order' => $validated['cg_order'],
                    'teacher_id' => $validated['teacher_id'] ?? null, // Can be null
                ]);
    
                // If a teacher is assigned, mark them as a class teacher
                if (!empty($validated['teacher_id'])) {
                    TeacherModel::where('id', $validated['teacher_id'])->update(['is_class_teacher' => '1']);
                }
    
                $message = 'Class group created successfully!';
            }
    
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => $message,
                'data' => $classGroup->makeHidden(['created_at', 'updated_at']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to create or update class group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update an existing Class Group
    public function update(Request $request, $id)
    {
        // Validation rules
        $validated = $request->validate([
            'ay_id' => 'sometimes|integer|min:1',
            'cg_name' => 'sometimes|string|max:255', // Update to "Nursery" or "Class 2"
            'cg_order' => 'sometimes|integer|min:1',
        ]);

        try {
            $classGroup = ClassGroupModel::find($id);

            if (!$classGroup) {
                return response()->json(['message' => 'Class group not found.'], 404);
            }

            // Update fields, fallback to existing data if null
            $classGroup->update([
                'ay_id' => $validated['ay_id'] ?? $classGroup->ay_id,
                'cg_name' => $validated['cg_name'] ?? $classGroup->cg_name,
                'cg_order' => $validated['cg_order'] ?? $classGroup->cg_order,
            ]);

            return response()->json([
                'message' => 'Class group updated successfully',
                'data' => $classGroup->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update class group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all class groups or a specific class group
    public function index(Request $request, $id = null)
    {
        if ($id) {
            // Fetch a specific class group
            $classGroup = ClassGroupModel::with('academicYear')->find($id);
    
            if ($classGroup) {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Class group fetched successfully',
                    'data' => $classGroup->makeHidden(['created_at', 'updated_at'])
                        ->append('academic_year_name')
                ], 200);
            }
    
            return response()->json(['message' => 'Class group not found'], 404);
        } else {
            // Validate `ay_id` when fetching all records
            $validated = $request->validate([
                'ay_id' => 'required|integer|exists:t_academic_years,id',
            ]);
    
            $ay_id = $validated['ay_id'];
    
            // Fetch the academic year name
            $academicYear = AcademicYearModel::find($ay_id);
            if (!$academicYear) {
                return response()->json(['message' => 'Academic year not found'], 404);
            }
    
            // Fetch all class groups filtered by `ay_id`
            $classGroups = ClassGroupModel::where('ay_id', $ay_id)
                ->orderBy('cg_order')
                ->get();
    
            $classGroups->each(function ($group) use ($academicYear) {
                $group->makeHidden(['created_at', 'updated_at', 'ay_id', 'cg_order']);
                $group->academic_year_name = $academicYear->ay_name;
            });
    
            return $classGroups->isNotEmpty()
                ? response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Class groups fetched successfully',
                    'data' => $classGroups,
                    'academic_year_name' => $academicYear->ay_name,
                    'count' => $classGroups->count()
                ], 200)
                : response()->json([
                    'code' => 200,
                    'status' => false,
                    'message' => 'No class groups available.',
                    'academic_year_name' => $academicYear->ay_name
                ]);
        }
    }

    public function viewAll(Request $request)
{
    try {
        // Validate request
        $validated = $request->validate([
            'ay_id' => 'required|integer|exists:t_academic_years,id',
            'search' => 'nullable|string|max:255', // Search class name
            'offset' => 'nullable|integer|min:0',  // Pagination offset
            'limit' => 'nullable|integer|min:1|max:100', // Pagination limit (max 100)
        ]);

        $ay_id = $validated['ay_id'];
        $search = $validated['search'] ?? null;
        $offset = $validated['offset'] ?? 0;
        $limit = $validated['limit'] ?? 10;

        // Fetch academic year
        $academicYear = AcademicYearModel::find($ay_id);
        if (!$academicYear) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Academic year not found.',
            ], 404);
        }

        // Get total count before applying offset and limit
        $totalCountQuery = DB::table('t_class_groups as cg')
            ->where('cg.ay_id', $ay_id);

        if (!empty($search)) {
            $totalCountQuery->where('cg.cg_name', 'LIKE', '%' . $search . '%');
        }

        $totalCount = $totalCountQuery->count(); // Get total count

        // Fetch class groups with teacher names and student count
        $classGroups = DB::table('t_class_groups as cg')
            ->leftJoin('t_teachers as t', 'cg.teacher_id', '=', 't.id')
            ->leftJoin('t_student_classes as sc', 'cg.id', '=', 'sc.cg_id')
            ->where('cg.ay_id', $ay_id)
            ->selectRaw("
                cg.id as cg_id,
                cg.cg_name,
                cg.teacher_id,
                t.name AS class_teacher_name,
                COUNT(DISTINCT sc.st_id) AS total_students
            ")
            ->when(!empty($search), function ($query) use ($search) {
                $query->where('cg.cg_name', 'LIKE', '%' . $search . '%');
            })
            ->groupBy('cg.id', 'cg.cg_name', 'cg.teacher_id', 't.name')
            ->orderBy('cg.cg_order')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Class groups fetched successfully.',
            'academic_year_name' => $academicYear->ay_name,
            'data' => $classGroups,
            'count' => $totalCount, // Correct total count
            'offset' => $offset,
            'limit' => $limit,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching class groups.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    // Delete a class group
    public function destroy($id)
    {
        try {
            $classGroup = ClassGroupModel::find($id);

            if (!$classGroup) {
                return response()->json(['message' => 'Class group not found.'], 404);
            }

            $classGroup->delete();

            return response()->json(['code'=>200,'message' => 'Class group deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code'=>500,
                'message' => 'Failed to delete class group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // csv
    public function importCsv()
    {
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/class_group.csv');
    
            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }
    
            // Truncate the table before import
            ClassGroupModel::truncate();
    
            // Fetch the CSV content
            $csvContent = file_get_contents($csvFilePath);
    
            // Parse the CSV content using League\Csv
            $csvReader = Reader::createFromString($csvContent);
    
            // Set the header offset (first row as headers)
            $csvReader->setHeaderOffset(0);
    
            // Process the CSV records
            $records = (new Statement())->process($csvReader);
    
            foreach ($records as $row) {
                // Insert records into the table
                ClassGroupModel::create([
                    'id' => $row['cg_id'],
                    'ay_id' => $row['ay_id'],
                    'cg_name' => $row['cg_name'],
                    'cg_order' => $row['cg_order'],
                ]);
            }
    
            return response()->json([
                'message' => 'Class Groups CSV imported successfully!',
            ], 200);
    
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'Failed to import Class Groups CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
