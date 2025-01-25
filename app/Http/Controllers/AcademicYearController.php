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
    public function index($id = null)
    {
        if ($id) {
            // Fetch specific record
            $academicYear = AcademicYearModel::find($id);

            if ($academicYear) {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Academic years fetched successfully',
                    'data' => $academicYear->makeHidden(['id', 'created_at', 'updated_at'])
                ], 200);
            }

            return response()->json([
                'code' => 200,
                'status'=> true,
                'data'=>[],
                'message' => 'Academic year not found'
            ], 200);
        } else {
            // Fetch all records
            $academicYears = AcademicYearModel::all();

            $academicYears->each(function ($year) {
                $year->makeHidden(['created_at', 'updated_at']);
            });

            return $academicYears->isNotEmpty()
                ? response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Academic years fetched successfully',
                    'data' => array_slice($academicYears->toArray(), 0, 10)
                ], 200)
                : response()->json([
                    'code' => 200,
                    'status'=> true,
                    'data'=>[],
                    'message' => 'No academic years available.'
                ], 200);
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
