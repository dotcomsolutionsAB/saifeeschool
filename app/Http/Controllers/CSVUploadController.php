<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubjectFMModel;
use App\Models\TermModel;
use App\Models\AcademicYearModel;
use App\Models\ClassGroupModel;
use App\Models\SubjectModel;
use Illuminate\Support\Facades\Validator;

class CSVUploadController extends Controller
{
    /**
     * Upload CSV data into `t_subjectFM`
     */
    public function uploadSubjectFM(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|mimes:csv,txt|max:2048',
            ]);

            $file = $request->file('csv_file');
            $filePath = $file->getRealPath();
            $csvData = array_map('str_getcsv', file($filePath));

            if (empty($csvData) || count($csvData) < 2) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'CSV file is empty or invalid!',
                ], 400);
            }

            // Remove the header row
            $header = array_shift($csvData);

            foreach ($csvData as $row) {
                // Ensure row has enough columns
                if (count($row) < 5) continue;

                list($ay_name, $cg_name, $subject_name, $full_marks, $pass_marks) = $row;

                // Find or create required foreign keys
                $academicYear = AcademicYearModel::where('ay_name', $ay_name)->first();
                $classGroup = ClassGroupModel::where('cg_name', $cg_name)->first();
                $subject = SubjectModel::where('subj_name', $subject_name)->first();

                if (!$academicYear || !$classGroup || !$subject) {
                    continue; // Skip invalid rows
                }

                // Insert data into `t_subjectFM`
                SubjectFMModel::updateOrCreate(
                    [
                        'ay_id' => $academicYear->id,
                        'cg_id' => $classGroup->id,
                        'subject_id' => $subject->id
                    ],
                    [
                        'full_marks' => $full_marks,
                        'pass_marks' => $pass_marks
                    ]
                );
            }

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Subject FM data uploaded successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while uploading Subject FM data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload CSV data into `t_terms`
     */
    public function uploadTerms(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|mimes:csv,txt|max:2048',
            ]);

            $file = $request->file('csv_file');
            $filePath = $file->getRealPath();
            $csvData = array_map('str_getcsv', file($filePath));

            if (empty($csvData) || count($csvData) < 2) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'CSV file is empty or invalid!',
                ], 400);
            }

            // Remove the header row
            $header = array_shift($csvData);

            foreach ($csvData as $row) {
                // Ensure row has enough columns
                if (count($row) < 4) continue;

                list($ay_name, $cg_name, $term, $term_name) = $row;

                // Find or create required foreign keys
                $academicYear = AcademicYearModel::where('ay_name', $ay_name)->first();
                $classGroup = ClassGroupModel::where('cg_name', $cg_name)->first();

                if (!$academicYear || !$classGroup) {
                    continue; // Skip invalid rows
                }

                // Insert data into `t_terms`
                TermModel::updateOrCreate(
                    [
                        'ay_id' => $academicYear->id,
                        'cg_id' => $classGroup->id,
                        'term' => $term
                    ],
                    [
                        'term_name' => $term_name
                    ]
                );
            }

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Terms data uploaded successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while uploading Terms data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}