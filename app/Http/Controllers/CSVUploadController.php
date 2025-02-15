<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TTermModel;
use App\Models\SubjectFMModel;
use App\Models\ClassGroupModel;
use App\Models\AcademicYearModel;
use App\Models\SubjectModel;
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
                // Ensure required fields exist
                if (count($row) < 5) {
                    continue;
                }

                // Check if term already exists
                $existingTerm = TTermModel::where([
                    'ay_id' => trim($row[1]),
                    'cg_id' => trim($row[2]),
                    'term' => trim($row[3]),
                ])->exists();

                if (!$existingTerm) {
                    TTermModel::create([
                        'ay_id' => trim($row[1]),
                        'cg_id' => trim($row[2]),
                        'term' => trim($row[3]),
                        'term_name' => trim($row[4]),
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
     * Insert Subject FM Data (Fixes Issues)
     */
   /**
 * Insert Subject FM Data (Fixed)
 */
private function insertSubjectFM($data)
{
    try {
        DB::beginTransaction();

        foreach ($data as $row) {
            // Ensure required fields exist
            if (count($row) < 11) {
                continue;
            }

            $subj_id = trim($row[1]);
            $subj_name = trim($row[2]);
            $subj_init = trim($row[3]);
            $cg_id = trim($row[4]);
            $term_id = trim($row[5]);
            $type = trim($row[6]);
            $theory = !empty($row[7]) ? trim($row[7]) : null;
            $oral = !empty($row[8]) ? trim($row[8]) : null;
            $prac = !empty($row[9]) ? trim($row[9]) : null;
            $marks = !empty($row[10]) ? trim($row[10]) : 0;

            // 🚀 **Fetch ay_id from t_class_groups using cg_id**
            $classGroup = ClassGroupModel::where('id', $cg_id)->first();
            if (!$classGroup) {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => "Class group with ID $cg_id not found.",
                ], 400);
            }
            $ay_id = $classGroup->ay_id; // ✅ **Correctly fetch ay_id**

            // 🚀 **Validate existence of foreign keys**
            $subjectExists = SubjectModel::where('id', $subj_id)->exists();
            $termExists = TTermModel::where('id', $term_id)->exists();

            if ($subjectExists && $termExists) {
                // 🚀 **Check if subjectFM already exists (Prevent Duplicates)**
                $existingFM = SubjectFMModel::where([
                    'subj_id' => $subj_id,
                    'cg_id' => $cg_id,
                    'term_id' => $term_id,
                    'ay_id' => $ay_id, // ✅ **Ensure ay_id is used**
                ])->exists();

                if (!$existingFM) {
                    SubjectFMModel::create([
                        'subj_id' => $subj_id,
                        'subj_name' => $subj_name,
                        'subj_init' => $subj_init,
                        'cg_id' => $cg_id,
                        'term_id' => $term_id,
                        'ay_id' => $ay_id, // ✅ **Added ay_id to prevent error**
                        'type' => $type,
                        'theory' => $theory,
                        'oral' => $oral,
                        'prac' => $prac,
                        'marks' => $marks,
                        'created_at' => now(),
                        'updated_at' => now(),
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