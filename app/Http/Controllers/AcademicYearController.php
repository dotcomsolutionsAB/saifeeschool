<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYearModel;
use League\Csv\Reader;
use League\Csv\Statement;

class AcademicYearController extends Controller
{
    //
    // Create a new Academic Year
    public function create(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'sch_id' => 'required|string|max:10',
            'ay_name' => 'required|string|max:100',
            'ay_start_year' => 'required|string|max:100',
            'ay_start_month' => 'required|string|max:10',
            'ay_end_year' => 'required|string|max:10',
            'ay_end_month' => 'required|string|max:10',
            'ay_current' => 'required|string|in:0,1',
        ]);

        try {
            // Create a new academic year
            // $academicYear = AcademicYearModel::create($validated);
            $academicYear = AcademicYearModel::create([
                'sch_id' => $validated['sch_id'],
                'ay_name' => $validated['ay_name'],
                'ay_start_year' => $validated['ay_start_year'],
                'ay_start_month' => $validated['ay_start_month'],
                'ay_end_year' => $validated['ay_end_year'],
                'ay_end_month' => $validated['ay_end_month'],
                'ay_current' => $validated['ay_current'],
            ]);

            return response()->json([
                'message' => 'Academic year created successfully',
                'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create academic year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing Academic Year
    public function update(Request $request, $id)
    {
        // Validation rules
        $validated = $request->validate([
            'sch_id' => 'sometimes|string|max:10',
            'ay_name' => 'sometimes|string|max:100',
            'ay_start_year' => 'sometimes|string|max:100',
            'ay_start_month' => 'sometimes|string|max:10',
            'ay_end_year' => 'sometimes|string|max:10',
            'ay_end_month' => 'sometimes|string|max:10',
            'ay_current' => 'sometimes|string|in:0,1',
        ]);

        try {
            $academicYear = AcademicYearModel::find($id);

            if (!$academicYear) {
                return response()->json(['message' => 'Academic year not found.'], 404);
            }

            // Update fields, fallback to existing data if null
            $academicYear->update([
                'sch_id' => $validated['sch_id'] ?? $academicYear->sch_id,
                'ay_name' => $validated['ay_name'] ?? $academicYear->ay_name,
                'ay_start_year' => $validated['ay_start_year'] ?? $academicYear->ay_start_year,
                'ay_start_month' => $validated['ay_start_month'] ?? $academicYear->ay_start_month,
                'ay_end_year' => $validated['ay_end_year'] ?? $academicYear->ay_end_year,
                'ay_end_month' => $validated['ay_end_month'] ?? $academicYear->ay_end_month,
                'ay_current' => $validated['ay_current'] ?? $academicYear->ay_current,
            ]);

            return response()->json([
                'message' => 'Academic year updated successfully',
                'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update academic year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch all academic years or a specific one
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    
    public function index($id = null)
    {
        try {
            if ($id) {
                // Fetch specific academic year
                $academicYear = AcademicYearModel::find($id);
    
                if (!$academicYear) {
                    return response()->json([
                        'code' => 200,
                        'status' => true,
                        'data' => [],
                        'message' => 'Academic year not found'
                    ], 200);
                }
    
                // Convert start & end month to month names
                $academicYear->ay_start_month = Carbon::createFromFormat('m', $academicYear->ay_start_month)->format('F');
                $academicYear->ay_end_month = Carbon::createFromFormat('m', $academicYear->ay_end_month)->format('F');
    
                // Count total classes & fee plans associated with this academic year
                $totalClasses = DB::table('t_class_groups')->where('ay_id', $academicYear->id)->count();
                $totalFeePlans = DB::table('t_fee_plans')->where('ay_id', $academicYear->id)->count();
    
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Academic year fetched successfully',
                    'data' => [
                        'ay_id' => (string) $academicYear->id,
                        'sch_id' => (string) $academicYear->sch_id,
                        'ay_name' => $academicYear->ay_name,
                        'ay_start_year' => (string) $academicYear->ay_start_year,
                        'ay_start_month' => $academicYear->ay_start_month,
                        'ay_end_year' => (string) $academicYear->ay_end_year,
                        'ay_end_month' => $academicYear->ay_end_month,
                        'ay_current' => (string) $academicYear->ay_current,
                        'total_classes' => (string) $totalClasses,
                        'total_fee_plans' => (string) $totalFeePlans,
                    ]
                ], 200);
            } else {
                // Fetch all academic years
                $academicYears = AcademicYearModel::all();
    
                if ($academicYears->isEmpty()) {
                    return response()->json([
                        'code' => 200,
                        'status' => true,
                        'data' => [],
                        'message' => 'No academic years available.'
                    ], 200);
                }
    
                // Map through academic years, converting month numbers to names & counting related data
                $formattedAcademicYears = $academicYears->map(function ($year) {
                    return [
                        'ay_id' => (string) $year->id,
                        'sch_id' => (string) $year->sch_id,
                        'ay_name' => $year->ay_name,
                        'ay_start_year' => (string) $year->ay_start_year,
                        'ay_start_month' => Carbon::createFromFormat('m', $year->ay_start_month)->format('F'),
                        'ay_end_year' => (string) $year->ay_end_year,
                        'ay_end_month' => Carbon::createFromFormat('m', $year->ay_end_month)->format('F'),
                        'ay_current' => (string) $year->ay_current,
                        'total_classes' => (string) DB::table('t_class_groups')->where('ay_id', $year->id)->count(),
                        'total_fee_plans' => (string) DB::table('t_fee_plans')->where('ay_id', $year->id)->count(),
                    ];
                });
    
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Academic years fetched successfully',
                    'data' => $formattedAcademicYears
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching academic years.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Delete an Academic Year
    public function destroy($id)
    {
        try {
            $academicYear = AcademicYearModel::find($id);

            if (!$academicYear) {
                return response()->json(['message' => 'Academic year not present.'], 404);
            }

            $academicYear->delete();

            return response()->json(['message' => 'Academic year deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete academic year.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // csv
    public function importCsv()
    {
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/academic_year.csv');
    
            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }
    
            // **Truncate the table at the beginning**
            AcademicYearModel::truncate();

            // Fetch the CSV content
            $csvContent = file_get_contents($csvFilePath);
    
            // Parse the CSV content using League\Csv
            $csvReader = Reader::createFromString($csvContent);
    
            // Set the header offset (first row as headers)
            $csvReader->setHeaderOffset(0);
    
            // Process the CSV records
            $records = (new Statement())->process($csvReader);
    
            foreach ($records as $row) {
                // Map old table columns to new table fields
                AcademicYearModel::updateOrCreate(
                    ['id' => $row['ay_id']], // Match by primary key (id)
                    [
                        'sch_id' => $row['sch_id'],
                        'ay_name' => $row['ay_name'],
                        'ay_start_year' => $row['ay_start_year'],
                        'ay_start_month' => $row['ay_start_month'],
                        'ay_end_year' => $row['ay_end_year'],
                        'ay_end_month' => $row['ay_end_month'],
                        'ay_current' => $row['ay_current'],
                    ]
                );
            }
    
            return response()->json([
                'message' => 'Academic Year CSV imported successfully!',
            ], 200);
    
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'Failed to import CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
