<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewAdmissionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class NewAdmissionController extends Controller
{
    //
    public function register(Request $request)
    {
        $validated = $request->validate([
            'application_no' => 'required|string|max:100|unique:t_new_admission,application_no',
            'ay_id' => 'required|integer|exists:t_academic_years,id',
            'class' => 'required|string|max:100',
            'date' => 'required|date',
            'first_name' => 'required|string|max:256',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:m,f',
            'date_of_birth' => 'required|date',
            'last_school' => 'nullable|string|max:1000',
            'last_school_address' => 'nullable|string|max:1000',
            'aadhaar_no' => 'nullable|digits:12',

            // Father's details
            'father_first_name' => 'required|string|max:256',
            'father_last_name' => 'required|string|max:256',
            'father_name' => 'required|string|max:512',
            'father_occupation' => 'required|in:employed,business,none',
            'father_employer' => 'nullable|required_if:father_occupation,employed|string|max:256',
            'father_designation' => 'nullable|required_if:father_occupation,employed|string|max:256',
            'father_business' => 'nullable|required_if:father_occupation,business|string|max:1000',
            'father_business_nature' => 'nullable|required_if:father_occupation,business|string|max:1000',
            'father_work_business_address' => 'nullable|required_if:father_occupation,employed,business|string|max:1000',
            'father_monthly_income' => 'nullable|required_if:father_occupation,employed,business|string|max:100',
            'father_mobile' => 'nullable|digits:10',
            'father_email' => 'nullable|email|max:256',

            // Mother's details
            'mother_first_name' => 'required|string|max:256',
            'mother_last_name' => 'required|string|max:256',
            'mother_name' => 'required|string|max:512',
            'mother_occupation' => 'required|in:employed,business,none',
            'mother_employer' => 'nullable|required_if:mother_occupation,employed|string|max:256',
            'mother_designation' => 'nullable|required_if:mother_occupation,employed|string|max:256',
            'mother_business' => 'nullable|required_if:mother_occupation,business|string|max:1000',
            'mother_business_nature' => 'nullable|required_if:mother_occupation,business|string|max:1000',
            'mother_work_business_address' => 'nullable|required_if:mother_occupation,employed,business|string|max:1000',
            'mother_monthly_income' => 'nullable|required_if:mother_occupation,employed,business|string|max:100',
            'mother_mobile' => 'nullable|digits:10',
            'mother_email' => 'nullable|email|max:256',

            // Address details
            'address_1' => 'required|string|max:1000',
            'address_2' => 'nullable|string|max:1000',
            'city' => 'required|string|max:256',
            'state' => 'required|string|max:256',
            'country' => 'required|string|max:256',
            'pincode' => 'required|digits:6',

            // Other information
            'ad_paid' => 'required|in:0,1',
            'transaction_id' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'interview_date' => 'required|date',
            'interview_status' => 'required|in:0,1',
            'added_to_school' => 'required|in:0,1',
            'comments' => 'nullable|string',
            'printed' => 'required|in:0,1',
        ]);

        try {
            $admission = NewAdmissionModel::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Admission record created successfully.',
                'data' => $admission->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create admission record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View admission records (all or by ID).
     */
    public function view(Request $request, $id = null)
    {
        if ($id) {
            $admission = NewAdmissionModel::find($id);

            if (!$admission) {
                return response()->json(['message' => 'Admission record not found.'], 404);
            }

            return response()->json(['message' => 'Admission record fetched successfully!', 'count' => count($admission), 'data' => $admission->makeHidden(['id', 'created_at', 'updated_at'])], 200);
        }

        $admissions = NewAdmissionModel::paginate($request->get('per_page', 10));

        return response()->json(['message' => 'Admission records fetched successfully!', 'count' => count($admissions), 'data' => $admissions->makeHidden(['id', 'created_at', 'updated_at'])], 200);
    }

     /**
     * Delete an admission record.
     */
    public function delete($id)
    {
        $admission = NewAdmissionModel::find($id);

        if (!$admission) {
            return response()->json(['message' => 'Admission record not found.'], 404);
        }

        $admission->delete();

        return response()->json(['message' => 'Admission record deleted successfully!'], 200);
    }

    
    public function importCsv(Request $request)
    {
        try {
            // Define the path to the CSV file
            $csvFilePath = storage_path('app/public/new_admission.csv');
    
            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json([
                    'message' => 'CSV file not found at the specified path.',
                ], 404);
            }
    
            // Truncate the table before import
            NewAdmissionModel::truncate();
    
            // Read CSV file
            $csv = \League\Csv\Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset(0); // First row as header
    
            $records = (new \League\Csv\Statement())->process($csv);
    
            DB::beginTransaction();
    
            $batchSize = 1000; // Process 1000 records at a time
            $dataBatch = [];
    
            foreach ($records as $row) {
                try {
                    // Convert gender
                    $gender = strtolower($row['gender']) === 'male' ? 'm' : (strtolower($row['gender']) === 'female' ? 'f' : null);
    
                    if (!$gender) {
                        Log::error('Invalid gender value: ' . $row['gender']);
                        continue; // Skip rows with invalid gender
                    }
    
                    // Decode and process father_details
                    $fatherDetails = $this->decodeJsonField($row['father_details']);
                    $fatherOccupation = strtolower($fatherDetails['occupation'] ?? 'none');
    
                    // Handle father occupation
                    $fatherBusiness = $fatherOccupation === 'business' ? ($fatherDetails['business'] ?? null) : null;
                    $fatherBusinessNature = $fatherOccupation === 'business' ? ($fatherDetails['nature'] ?? null) : null;
                    $fatherEmployer = $fatherOccupation === 'employed' ? ($fatherDetails['employer'] ?? null) : null;
                    $fatherDesignation = $fatherOccupation === 'employed' ? ($fatherDetails['designation'] ?? null) : null;
                    $fatherMonthlyIncome = $fatherDetails['monthly_income'] ?? null;
    
                    // Decode and process mother_details
                    $motherDetails = $this->decodeJsonField($row['mother_details']);
                    $motherOccupation = strtolower($motherDetails['occupation'] ?? 'home-maker');
    
                    // Adjust mother occupation if needed
                    if ($motherOccupation === 'housewife') {
                        $motherOccupation = 'home-maker';
                    }
    
                    $motherBusiness = $motherOccupation === 'business' ? ($motherDetails['business'] ?? null) : null;
                    $motherBusinessNature = $motherOccupation === 'business' ? ($motherDetails['nature'] ?? null) : null;
                    $motherEmployer = $motherOccupation === 'employed' ? ($motherDetails['employer'] ?? null) : null;
                    $motherDesignation = $motherOccupation === 'employed' ? ($motherDetails['designation'] ?? null) : null;
                    $motherMonthlyIncome = $motherDetails['monthly_income'] ?? null;
    
                    // Decode and process address
                    $address = $this->decodeJsonField($row['address']);
                    $address1 = $address['address_1'] ?? null;
                    $address2 = $address['address_2'] ?? null;
                    $city = $address['city'] ?? null;
                    $state = $address['state'] ?? null;
                    $country = $address['country'] ?? null;
                    $pincode = $address['pincode'] ?? null;
    
                    // Process interview_date
                    $interviewDate = $row['interview_date'] ?? null;
                    if ($interviewDate === '0000-00-00' || !$interviewDate || !\Carbon\Carbon::hasFormat($interviewDate, 'Y-m-d')) {
                        $interviewDate = null; // Set to NULL if invalid or default value
                    }
    
                    $dataBatch[] = [
                        'application_no' => $row['application_no'],
                        'ay_id' => $row['ay_id'],
                        'class' => $row['class'],
                        'date' => $row['date'],
                        'first_name' => $row['name'],
                        'last_name' => $row['last_name'],
                        'gender' => $gender, // Store 'm' or 'f'
                        'date_of_birth' => $row['date_of_birth'],
                        'last_school' => $row['last_school'],
                        'last_school_address' => $row['last_school_address'],
                        'aadhaar_no' => $row['aadhaar_no'],
                        'father_first_name' => $fatherDetails['first_name'] ?? null,
                        'father_last_name' => $fatherDetails['last_name'] ?? null,
                        'father_name' => $fatherDetails['name'] ?? null,
                        'father_occupation' => $fatherOccupation,
                        'father_employer' => $fatherEmployer,
                        'father_designation' => $fatherDesignation,
                        'father_business' => $fatherBusiness,
                        'father_business_nature' => $fatherBusinessNature,
                        'father_monthly_income' => $fatherMonthlyIncome,
                        'father_mobile' => $fatherDetails['mobile'] ?? null,
                        'father_email' => $fatherDetails['email'] ?? null,
                        'father_work_business_address' => $fatherDetails['address'] ?? null,
                        'mother_first_name' => $motherDetails['first_name'] ?? null,
                        'mother_last_name' => $motherDetails['last_name'] ?? null,
                        'mother_name' => $motherDetails['name'] ?? null,
                        'mother_occupation' => $motherOccupation,
                        'mother_employer' => $motherEmployer,
                        'mother_designation' => $motherDesignation,
                        'mother_business' => $motherBusiness,
                        'mother_business_nature' => $motherBusinessNature,
                        'mother_monthly_income' => $motherMonthlyIncome,
                        'mother_mobile' => $motherDetails['mobile'] ?? null,
                        'mother_email' => $motherDetails['email'] ?? null,
                        'mother_work_business_address' => $motherDetails['address'] ?? null,
                        'siblings' => $row['siblings'],
                        'address_1' => $address1,
                        'address_2' => $address2,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'pincode' => $pincode,
                        'other_info' => $row['other_info'],
                        'ad_paid' => $row['ad_paid'],
                        'transaction_id' => $row['transaction_id'],
                        'transaction_date' => $row['transaction_date'],
                        'interview_date' => $interviewDate, // Use validated date
                        'interview_status' => $row['interview_status'],
                        'added_to_school' => $row['added_to_school'],
                        'comments' => $row['comments'],
                        'printed' => $row['printed'],
                    ];
    
                    if (count($dataBatch) >= $batchSize) {
                        NewAdmissionModel::insert($dataBatch); // Insert batch into the database
                        $dataBatch = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }
    
            // Insert remaining records
            if (!empty($dataBatch)) {
                NewAdmissionModel::insert($dataBatch);
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Data imported successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import data: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import data.', 'error' => $e->getMessage()], 500);
        }
    }
    
    


        /**
     * Decode JSON Field Safely
     */
    private function decodeJsonField($field)
    {
        if (empty($field)) {
            return [];
        }

        $decoded = json_decode($field, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON: ' . $field);
            return [];
        }

        return $decoded;
    }


}
