<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TermModel;
use App\Models\SubjectFMModel;
use App\Models\ClassGroupModel;
use App\Models\AcademicYearModel;
use App\Models\SubjectModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CSVUploadController extends Controller
{
    public function uploadCSV(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:2048',
                'type' => 'required|string|in:terms,subjectFM',
            ]);

            $file = $request->file('file');
            $type = $request->input('type');

            // Read CSV File
            $fileData = array_map('str_getcsv', file($file->getPathname()));

            // Remove header row
            $headers = array_shift($fileData);

            // Check type and insert data
            if ($type === 'terms') {
                return $this->insertTerms($fileData);
            } elseif ($type === 'subjectFM') {
                return $this->insertSubjectFM($fileData);
            }

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while uploading CSV data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Insert Terms Data
     */
    private function insertTerms($data)
    {
        try {
            DB::beginTransaction();

            foreach ($data as $row) {
                // Check if term already exists
                $existingTerm = TermModel::where([
                    'ay_id' => $row[1],
                    'cg_id' => $row[2],
                    'term' => $row[3],
                ])->exists();

                if (!$existingTerm) {
                    TermModel::create([
                        'ay_id' => $row[1],
                        'cg_id' => $row[2],
                        'term' => $row[3],
                        'term_name' => $row[4],
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Term data uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while uploading Term data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Insert Subject FM Data
     */
    private function insertSubjectFM($data)
    {
        try {
            DB::beginTransaction();

            foreach ($data as $row) {
                // Validate existence of foreign keys
                $subjectExists = SubjectModel::where('id', $row[1])->exists();
                $classGroupExists = ClassGroupModel::where('id', $row[4])->exists();
                $termExists = TermModel::where('id', $row[5])->exists();

                if ($subjectExists && $classGroupExists && $termExists) {
                    // Check if subjectFM already exists
                    $existingFM = SubjectFMModel::where([
                        'subj_id' => $row[1],
                        'cg_id' => $row[4],
                        'term_id' => $row[5],
                    ])->exists();

                    if (!$existingFM) {
                        SubjectFMModel::create([
                            'subj_id' => $row[1],
                            'subj_name' => $row[2],
                            'subj_init' => $row[3],
                            'cg_id' => $row[4],
                            'term_id' => $row[5],
                            'type' => $row[6],
                            'theory' => $row[7] ?? null,
                            'oral' => $row[8] ?? null,
                            'prac' => $row[9] ?? null,
                            'marks' => $row[10] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Subject FM data uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while uploading Subject FM data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}