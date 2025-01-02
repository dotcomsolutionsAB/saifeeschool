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

    
    // public function importCsv(Request $request)
    // {
    //     try {
    //         // Define the path to the CSV file
    //         $csvFilePath = storage_path('app/public/new_admission.csv');
    
    //         // Check if the file exists
    //         if (!file_exists($csvFilePath)) {
    //             return response()->json([
    //                 'message' => 'CSV file not found at the specified path.',
    //             ], 404);
    //         }
    
    //         // Truncate the table before import
    //         NewAdmissionModel::truncate();
    
    //         // Read CSV file
    //         $csv = \League\Csv\Reader::createFromPath($csvFilePath, 'r');
    //         $csv->setHeaderOffset(0); // First row as header
    
    //         $records = (new \League\Csv\Statement())->process($csv);
    
    //         DB::beginTransaction();
    
    //         $batchSize = 1000; // Process 1000 records at a time
    //         $dataBatch = [];
    
    //         foreach ($records as $row) {
    //             try {
    //                 // Convert gender
    //                 $gender = strtolower($row['gender']) === 'male' ? 'm' : (strtolower($row['gender']) === 'female' ? 'f' : null);
    
    //                 if (!$gender) {
    //                     Log::error('Invalid gender value: ' . $row['gender']);
    //                     continue; // Skip rows with invalid gender
    //                 }
    
    //                 // Decode and process father_details
    //                 $fatherDetails = $this->decodeJsonField($row['father_details']);
    //                 $fatherOccupation = strtolower($fatherDetails['occupation'] ?? 'none');
    
    //                 // Handle father occupation
    //                 $fatherBusiness = $fatherOccupation === 'business' ? ($fatherDetails['business'] ?? null) : null;
    //                 $fatherBusinessNature = $fatherOccupation === 'business' ? ($fatherDetails['nature'] ?? null) : null;
    //                 $fatherEmployer = $fatherOccupation === 'employed' ? ($fatherDetails['employer'] ?? null) : null;
    //                 $fatherDesignation = $fatherOccupation === 'employed' ? ($fatherDetails['designation'] ?? null) : null;
    //                 $fatherMonthlyIncome = $fatherDetails['monthly_income'] ?? null;
    
    //                 // Decode and process mother_details
    //                 $motherDetails = $this->decodeJsonField($row['mother_details']);
    //                 $motherOccupation = strtolower($motherDetails['occupation'] ?? 'home-maker');
    
    //                 // Adjust mother occupation if needed
    //                 if ($motherOccupation === 'housewife') {
    //                     $motherOccupation = 'home-maker';
    //                 }
    
    //                 $motherBusiness = $motherOccupation === 'business' ? ($motherDetails['business'] ?? null) : null;
    //                 $motherBusinessNature = $motherOccupation === 'business' ? ($motherDetails['nature'] ?? null) : null;
    //                 $motherEmployer = $motherOccupation === 'employed' ? ($motherDetails['employer'] ?? null) : null;
    //                 $motherDesignation = $motherOccupation === 'employed' ? ($motherDetails['designation'] ?? null) : null;
    //                 $motherMonthlyIncome = $motherDetails['monthly_income'] ?? null;
    
    //                 // Decode and process address
    //                 $address = $this->decodeJsonField($row['address']);
    //                 $address1 = $address['address_1'] ?? null;
    //                 $address2 = $address['address_2'] ?? null;
    //                 $city = $address['city'] ?? null;
    //                 $state = $address['state'] ?? null;
    //                 $country = $address['country'] ?? null;
    //                 $pincode = $address['pincode'] ?? null;
    
    //                 // Process interview_date
    //                 $interviewDate = $row['interview_date'] ?? null;
    //                 if ($interviewDate === '0000-00-00' || !$interviewDate || !\Carbon\Carbon::hasFormat($interviewDate, 'Y-m-d')) {
    //                     $interviewDate = null; // Set to NULL if invalid or default value
    //                 }
    
    //                 $dataBatch[] = [
    //                     'application_no' => $row['application_no'],
    //                     'ay_id' => $row['ay_id'],
    //                     'class' => $row['class'],
    //                     'date' => $row['date'],
    //                     'first_name' => $row['name'],
    //                     'last_name' => $row['last_name'],
    //                     'gender' => $gender, // Store 'm' or 'f'
    //                     'date_of_birth' => $row['date_of_birth'],
    //                     'last_school' => $row['last_school'],
    //                     'last_school_address' => $row['last_school_address'],
    //                     'aadhaar_no' => $row['aadhaar_no'],
    //                     'father_first_name' => $fatherDetails['first_name'] ?? null,
    //                     'father_last_name' => $fatherDetails['last_name'] ?? null,
    //                     'father_name' => $fatherDetails['name'] ?? null,
    //                     'father_occupation' => $fatherOccupation,
    //                     'father_employer' => $fatherEmployer,
    //                     'father_designation' => $fatherDesignation,
    //                     'father_business' => $fatherBusiness,
    //                     'father_business_nature' => $fatherBusinessNature,
    //                     'father_monthly_income' => $fatherMonthlyIncome,
    //                     'father_mobile' => $fatherDetails['mobile'] ?? null,
    //                     'father_email' => $fatherDetails['email'] ?? null,
    //                     'father_work_business_address' => $fatherDetails['address'] ?? null,
    //                     'mother_first_name' => $motherDetails['first_name'] ?? null,
    //                     'mother_last_name' => $motherDetails['last_name'] ?? null,
    //                     'mother_name' => $motherDetails['name'] ?? null,
    //                     'mother_occupation' => $motherOccupation,
    //                     'mother_employer' => $motherEmployer,
    //                     'mother_designation' => $motherDesignation,
    //                     'mother_business' => $motherBusiness,
    //                     'mother_business_nature' => $motherBusinessNature,
    //                     'mother_monthly_income' => $motherMonthlyIncome,
    //                     'mother_mobile' => $motherDetails['mobile'] ?? null,
    //                     'mother_email' => $motherDetails['email'] ?? null,
    //                     'mother_work_business_address' => $motherDetails['address'] ?? null,
    //                     'siblings' => $row['siblings'],
    //                     'address_1' => $address1,
    //                     'address_2' => $address2,
    //                     'city' => $city,
    //                     'state' => $state,
    //                     'country' => $country,
    //                     'pincode' => $pincode,
    //                     'other_info' => $row['other_info'],
    //                     'ad_paid' => $row['ad_paid'],
    //                     'transaction_id' => $row['transaction_id'],
    //                     'transaction_date' => $row['transaction_date'],
    //                     'interview_date' => $interviewDate, // Use validated date
    //                     'interview_status' => $row['interview_status'],
    //                     'added_to_school' => $row['added_to_school'],
    //                     'comments' => $row['comments'],
    //                     'printed' => $row['printed'],
    //                 ];
    
    //                 if (count($dataBatch) >= $batchSize) {
    //                     NewAdmissionModel::insert($dataBatch); // Insert batch into the database
    //                     $dataBatch = [];
    //                 }
    //             } catch (\Exception $e) {
    //                 Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
    //             }
    //         }
    
    //         // Insert remaining records
    //         if (!empty($dataBatch)) {
    //             NewAdmissionModel::insert($dataBatch);
    //         }
    
    //         DB::commit();
    
    //         return response()->json(['message' => 'Data imported successfully!'], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Failed to import data: ' . $e->getMessage());
    //         return response()->json(['message' => 'Failed to import data.', 'error' => $e->getMessage()], 500);
    //     }
    // }
    
//     public function importCsv(Request $request)
//     {
//         try {
//             // Define the path to the CSV file
//             $csvFilePath = storage_path('app/public/new_admission.csv');

//             // Check if the file exists
//             if (!file_exists($csvFilePath)) {
//                 return response()->json([
//                     'message' => 'CSV file not found at the specified path.',
//                 ], 404);
//             }

//             // Truncate the table before import
//             NewAdmissionModel::truncate();

//             // Read CSV file
//             $csv = \League\Csv\Reader::createFromPath($csvFilePath, 'r');
//             $csv->setHeaderOffset(0); // First row as header

//             $records = (new \League\Csv\Statement())->process($csv);

//             DB::beginTransaction();

//             $batchSize = 1000; // Process 1000 records at a time
//             $dataBatch = [];
//             $rowCount = 0;

//             foreach ($records as $row) {
//                 $rowCount++;
//                 try {
//                     // Convert gender
//                     $gender = strtolower($row['gender']) === 'male' ? 'm' : (strtolower($row['gender']) === 'female' ? 'f' : null);

//                     if (!$gender) {
//                         Log::error("Row $rowCount: Invalid gender value - " . $row['gender']);
//                         continue; // Skip rows with invalid gender
//                     }

//                     // Decode and process father_details
//                     $fatherDetails = $this->decodeJsondetails($row['father_details']);
//                     $fatherOccupation = isset($fatherDetails['occupation']) ? strtolower($fatherDetails['occupation']) : null;
//                     $fatherMobile = $fatherDetails['mobile'] ?? null;

//                     if ($fatherOccupation === 'no occupation') {
//                         $fatherOccupation = 'no-occupation';
//                     }

//                     if ($fatherOccupation === '') {
//                         $fatherOccupation = null;
//                     }

//                     $fatherMobile = $this->sanitizeMobile($fatherMobile, $rowCount, 'father_mobile');

//                     // Decode and process mother_details
//                     $motherDetails = $this->decodeJsonField($row['mother_details']);
//                     $motherOccupation = isset($motherDetails['occupation']) ? strtolower($motherDetails['occupation']) : null;

//                     if ($motherOccupation === 'housewife') {
//                         $motherOccupation = 'home-maker';
//                     }

//                     if ($motherOccupation === 'not applicable') {
//                         $motherOccupation = 'not-applicable';
//                     }

//                     if ($motherOccupation === '') {
//                         $motherOccupation = null;
//                     }


//                     $motherMobile = $this->sanitizeMobile($motherDetails['mobile'] ?? null, $rowCount, 'mother_mobile');

//                     // Decode and process address
//                     $address = $this->decodeJsonField($row['address']);

//                     // Process siblings
//                     $siblings = $this->decodeJsonField($row['siblings']);
//                     $siblingsData = $this->processSiblings($siblings);

//                     // Decode and process other_info
//                     $otherInfo = $this->decodeJsonFieldothers($row['other_info']);
//                     $attracted = $otherInfo['attracted'] ?? null;
//                     $strengths = $otherInfo['strengths'] ?? null;

//                     // Process interview_date
//                     $interviewDate = $row['interview_date'] ?? null;

//                     // Check for invalid dates like '0000-00-00' or other invalid formats
//                     if ($interviewDate === '0000-00-00' || !$interviewDate || !\Carbon\Carbon::hasFormat($interviewDate, 'Y-m-d')) {
//                         // Log::warning("Row {$row['application_no']}: Invalid interview_date - {$interviewDate}");
//                         $interviewDate = null; // Set to NULL if invalid or default value
//                     }

//                     // Prepare the data
//                     $dataBatch[] = array_merge([
//                         'id' => $row['id'],
//                         'application_no' => $row['application_no'],
//                         'ay_id' => $row['ay_id'],
//                         'class' => $row['class'],
//                         'date' => $row['date'],
//                         'first_name' => $row['name'],
//                         'last_name' => $row['last_name'],
//                         'gender' => $gender,
//                         'date_of_birth' => $row['date_of_birth'],
//                         'last_school' => $row['last_school'],
//                         'last_school_address' => $row['last_school_address'],
//                         'aadhaar_no' => $row['aadhaar_no'],
//                         'father_name' => $fatherDetails['name'] ?? null,
//                         'father_mobile' => $fatherMobile,
//                         'mother_mobile' => $motherMobile,
//                         'mother_occupation' => $motherOccupation,
//                         'address_1' => $address['address_1'] ?? null,
//                         'address_2' => $address['address_2'] ?? null,
//                         'city' => $address['city'] ?? null,
//                         'state' => $address['state'] ?? null,
//                         'country' => $address['country'] ?? null,
//                         'pincode' => $address['pincode'] ?? null,
//                         'attracted' => $attracted,
//                         'strengths' => $strengths,
//                         'interview_date' => $interviewDate,
//                     ], $siblingsData);

//                     // Insert data in batches
//                     if (count($dataBatch) >= $batchSize) {
//                         NewAdmissionModel::insert($dataBatch);
//                         $dataBatch = [];
//                     }

//                     // print_r($interviewDate);
//                     // echo "<pre>";
//                 } catch (\Exception $e) {
//                     Log::error("Row $rowCount: Error processing row - " . $e->getMessage());
//                 }
//             }

//             // Insert remaining records
//             if (!empty($dataBatch)) {
//                 NewAdmissionModel::insert($dataBatch);
//             }

//             DB::commit();

//             return response()->json(['message' => 'Data imported successfully!'], 200);
//         } catch (\Exception $e) {
//             DB::rollBack();
//             Log::error('Failed to import data: ' . $e->getMessage());
//             return response()->json(['message' => 'Failed to import data.', 'error' => $e->getMessage()], 500);
//         }
//     }

// /**
//  * Decode JSON Field Safely
//  */
// // private function decodeJsonField($field)
// // {
// //     if (empty($field)) {
// //         return [];
// //     }

// //     $decoded = json_decode($field, true);

// //     // Check if the field contains concatenated JSON strings
// //     if (json_last_error() !== JSON_ERROR_NONE) {
// //         Log::error('Invalid JSON: ' . $field);

// //         // Attempt to split and process each potential JSON string
// //         $jsonParts = preg_split('/(?<=})\s*(?={)/', $field);
// //         foreach ($jsonParts as $jsonPart) {
// //             $decodedPart = json_decode($jsonPart, true);
// //             if (json_last_error() === JSON_ERROR_NONE) {
// //                 Log::warning('Multiple JSON detected, using first valid part: ' . $jsonPart);
// //                 return $decodedPart; // Return the first valid JSON part
// //             }
// //         }

// //         // If no valid part is found, return empty array
// //         return [];
// //     }

// //     return $decoded;
// // }

// private function decodeJsonField($field)
// {
//     if (empty($field)) {
//         return [];
//     }

//     // Remove newline characters
//     $cleanedField = preg_replace('/\r|\n/', '', $field);

//     // Try decoding the JSON directly
//     $decoded = json_decode($cleanedField, true);

//     if (json_last_error() === JSON_ERROR_NONE) {
//         return $decoded; // Return decoded JSON if valid
//     }

//     // If JSON is invalid, split concatenated objects
//     Log::error('Invalid JSON: ' . $cleanedField . ' | Error: ' . json_last_error_msg());
//     $jsonParts = preg_split('/(?<=})\s*(?={)/', $cleanedField);

//     $decodedParts = [];
//     foreach ($jsonParts as $index => $jsonPart) {
//         $decodedPart = json_decode($jsonPart, true);
//         if (json_last_error() === JSON_ERROR_NONE) {
//             $decodedParts[] = $decodedPart;
//         } else {
//             Log::error("Part $index: Invalid JSON segment - " . $jsonPart . ' | Error: ' . json_last_error_msg());
//         }
//     }

//     return $decodedParts; // Return an array of decoded parts
// }


// private function decodeJsondetails($field)
// {
//     if (empty($field)) {
//         return [];
//     }

//     // Attempt to decode the JSON string
//     $decoded = json_decode($field, true);

//     if (json_last_error() === JSON_ERROR_NONE) {
//         return $decoded; // Return decoded JSON if valid
//     }

//     // Log the invalid JSON and error
//     Log::error('Invalid JSON: ' . $field . ' | Error: ' . json_last_error_msg());

//     // Attempt to split and process concatenated JSON strings
//     $jsonParts = preg_split('/(?<=})\s*(?={)/', $field);
//     foreach ($jsonParts as $index => $jsonPart) {
//         $decodedPart = json_decode($jsonPart, true);
//         if (json_last_error() === JSON_ERROR_NONE) {
//             // Log::warning('Multiple JSON detected. Using part ' . ($index + 1) . ': ' . $jsonPart);
//             // print_r($decodedPart);
//             // echo "<pre>";
//             return $decodedPart; // Return the first valid JSON part
//         }
//     }

//     // If no valid part is found, log a detailed message
//     // Log::error('No valid JSON parts found in field: ' . $field);

//     return []; // Return empty array if decoding fails
// }

// private function decodeJsonFieldothers($field)
// {
//     if (empty($field)) {
//         return [];
//     }

//     // Remove all new line characters
//     $cleanedJson = preg_replace('/\r|\n/', '', $field);

//     // Decode the cleaned JSON
//     $otherInfo = json_decode($cleanedJson, true);

//     if (json_last_error() === JSON_ERROR_NONE) {
//         return $otherInfo; // Successfully decoded
//     }

//     // Log the error with details
//     Log::error('Error decoding JSON: ' . json_last_error_msg() . ' | Original field: ' . $field);
//     return []; // Return an empty array if decoding fails
// }


// /**
//  * Sanitize Mobile Number
//  */
// private function sanitizeMobile($mobile, $rowCount, $field)
// {
//     if ($mobile && strlen($mobile) > 25) {
//         Log::error("Row $rowCount: $field exceeds allowed length - $mobile");
//         return null;
//     }
//     return $mobile;
// }

// /**
//  * Process Siblings Data
//  */
// private function processSiblings($siblings)
// {
//     $siblingsData = [
//         'siblings_name1' => null,
//         'siblings_class1' => null,
//         'siblings_roll_no1' => null,
//         'siblings_name2' => null,
//         'siblings_class2' => null,
//         'siblings_roll_no2' => null,
//         'siblings_name3' => null,
//         'siblings_class3' => null,
//         'siblings_roll_no3' => null,
//     ];

//     if (isset($siblings[0]['name'])) {
//         foreach ($siblings as $index => $sibling) {
//             if ($index >= 3) break; // Limit to 3 siblings
//             $siblingsData["siblings_name" . ($index + 1)] = $sibling['name'] ?? null;
//             $siblingsData["siblings_class" . ($index + 1)] = $sibling['class'] ?? null;
//             $siblingsData["siblings_roll_no" . ($index + 1)] = $sibling['roll_no'] ?? null;
//         }
//     }

//     return $siblingsData;
// }

// ...
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

        $dataBatch = [];
        $rowCount = 0;
        $batchSize = 1000;

        foreach ($records as $row) {
            $rowCount++;
            try {
                // Convert gender
                $gender = strtolower($row['gender']) === 'male' ? 'm' : (strtolower($row['gender']) === 'female' ? 'f' : null);
                if (!$gender) {
                    Log::error("Row $rowCount: Invalid gender value - " . $row['gender']);
                    continue;
                }

                // Decode and process father_details
                $fatherDetails = $this->decodeJsonFieldWithFallback($row['father_details'], $rowCount);
                // $fatherMobile = $this->sanitizeField($fatherDetails['mobile'] ?? null, $rowCount, 'father_mobile');
                // $fatherDetails = $this->sanitizeJsonField($row['father_details'] ?? '', $rowCount);

                $fatherMobile = $fatherDetails['mobile'] ?? null;

                $fatherOccupation = strtolower($fatherDetails['occupation'] ?? '');
                if ($fatherOccupation === 'no occupation') {
                    $fatherOccupation = 'no-occupation';
                }

                if ($fatherOccupation === '') {
                    $fatherOccupation = null;
                }

                // Decode and process mother_details
                $motherDetails = $this->decodeJsonFieldWithFallback($row['mother_details'], $rowCount);
                // $motherMobile = $this->sanitizeField($motherDetails['mobile'] ?? null, $rowCount, 'mother_mobile');

                // $motherDetails = $this->sanitizeJsonField($row['mother_details'] ?? '', $rowCount);

                $motherMobile = $motherDetails['mobile'] ?? null;

                $motherOccupation = strtolower($motherDetails['occupation'] ?? '');
                if ($motherOccupation === 'housewife') {
                    $motherOccupation = 'home-maker';
                }

                if ($motherOccupation === 'not applicable') {
                    $motherOccupation = 'not-applicable';
                }

                if ($motherOccupation === '') {
                    $motherOccupation = null;
                }

                // Decode and process address
                $address = $this->decodeJsonFieldWithFallback($row['address'], $rowCount);

                // Decode and process siblings
                // $siblings = $this->decodeJsonFieldWithFallback($row['siblings'], $rowCount);

                $siblings = $this->sanitizeJsonField($row['siblings'] ?? '', $rowCount);

                $siblingsData = $this->processSiblings($siblings);

                // Decode and process other_info
                // $otherInfo = $this->decodeJsonFieldWithFallback($row['other_info'], $rowCount);
                $otherInfo = $this->sanitizeJsonField($row['other_info'] ?? '', $rowCount);
                $attracted = $otherInfo['attracted'] ?? null;
                $strengths = $otherInfo['strengths'] ?? null;

                  // Additional processing logic (e.g., handling specific fields)
                  $fatherMobile = $this->sanitizeMobile($fatherMobile, $rowCount, 'father_mobile');
                  $motherMobile = $this->sanitizeMobile($motherMobile, $rowCount, 'mother_mobile');

                // Validate and process interview_date
                $interviewDate = $row['interview_date'] ?? null;
                if ($interviewDate === '0000-00-00' || !$interviewDate || !\Carbon\Carbon::hasFormat($interviewDate, 'Y-m-d')) {
                    $interviewDate = null;
                }

                print_r($fatherMobile);
                echo "<pre>";

                // Prepare the data
                $dataBatch[] = array_merge([
                    'id' => $row['id'],
                    'application_no' => $row['application_no'],
                    'ay_id' => $row['ay_id'],
                    'class' => $row['class'],
                    'date' => $row['date'],
                    'first_name' => $row['name'],
                    'last_name' => $row['last_name'],
                    'gender' => $gender,
                    'date_of_birth' => $row['date_of_birth'],
                    'last_school' => $row['last_school'],
                    'last_school_address' => $row['last_school_address'],
                    'aadhaar_no' => $row['aadhaar_no'],
                    'father_name' => $fatherDetails['name'] ?? null,
                    'father_mobile' => $fatherMobile,
                    'mother_mobile' => $motherMobile,
                    'mother_occupation' => $motherOccupation,
                    'address_1' => $address['address_1'] ?? null,
                    'address_2' => $address['address_2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'state' => $address['state'] ?? null,
                    'country' => $address['country'] ?? null,
                    'pincode' => $address['pincode'] ?? null,
                    'attracted' => $attracted,
                    'strengths' => $strengths,
                    'interview_date' => $interviewDate,
                ], $siblingsData);

                // Insert data in batches
                if (count($dataBatch) >= $batchSize) {
                    NewAdmissionModel::insert($dataBatch);
                    $dataBatch = [];
                }
            } catch (\Exception $e) {
                Log::error("Row $rowCount: Error processing row - " . $e->getMessage());
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
 * Decode JSON Field with Fallback
 */
private function decodeJsonFieldWithFallback($field, $rowCount)
{
    if (empty($field)) {
        return [];
    }

    // Remove newlines and any unwanted spaces
    $cleanedField = preg_replace('/\r|\n/', '', $field);

    // Try decoding the JSON directly
    $decoded = json_decode($cleanedField, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Handle concatenated JSON objects
    $jsonParts = preg_split('/(?<=})\s*(?={)/', $cleanedField);
    foreach ($jsonParts as $index => $jsonPart) {
        $decodedPart = json_decode($jsonPart, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedPart;
        }
    }

    Log::error("Row $rowCount: Error decoding JSON: " . json_last_error_msg() . " | Original field: $field");
    return [];
}

/**
 * Sanitize Field
 */
private function sanitizeField($value, $rowCount, $fieldName)
{
    if (!is_string($value)) {
        return $value;
    }

    // Remove invalid Unicode characters
    $sanitized = preg_replace('/[^\x20-\x7E]/u', '', $value);

    // Log a warning if sanitization altered the original value
    if ($sanitized !== $value) {
        Log::warning("Row $rowCount: Sanitized $fieldName. Original: $value | Sanitized: $sanitized");
    }

    return $sanitized;
}

/**
 * Process Siblings Data
 */
private function processSiblings($siblings)
{
    $siblingsData = [
        'siblings_name1' => null,
        'siblings_class1' => null,
        'siblings_roll_no1' => null,
        'siblings_name2' => null,
        'siblings_class2' => null,
        'siblings_roll_no2' => null,
        'siblings_name3' => null,
        'siblings_class3' => null,
        'siblings_roll_no3' => null,
    ];

    if (!empty($siblings)) {
        foreach ($siblings as $index => $sibling) {
            if ($index >= 3) break; // Limit to 3 siblings
            $siblingsData["siblings_name" . ($index + 1)] = $sibling['name'] ?? null;
            $siblingsData["siblings_class" . ($index + 1)] = $sibling['class'] ?? null;
            $siblingsData["siblings_roll_no" . ($index + 1)] = $sibling['roll_no'] ?? null;
        }
    }

    return $siblingsData;
}

private function sanitizeJsonField($jsonField, $rowNumber)
{
    if (empty($jsonField)) {
        return [];
    }

    // Remove line breaks and excess whitespace
    $cleanedJson = preg_replace('/\r|\n/', ' ', $jsonField);

    // Attempt to decode the cleaned JSON
    $decoded = json_decode($cleanedJson, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded; // Return valid JSON
    }

    // Log error if decoding fails
    Log::error("Row $rowNumber: Error decoding JSON: " . json_last_error_msg() . " | Original field: $jsonField");

    // Attempt to manually fix common issues
    $cleanedJson = str_replace(['\\"', '\='], ['"', '='], $cleanedJson);

    // Retry decoding
    $decoded = json_decode($cleanedJson, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // If still invalid, return empty array and log
    Log::error("Row $rowNumber: JSON field remains invalid after cleaning. Field: $jsonField");
    return [];
}

/**
 * Sanitize Mobile Number
 */
private function sanitizeMobile($mobile, $rowCount, $field)
{
    // Check if the mobile number exists and its length
    if ($mobile && strlen($mobile) > 25) {
        Log::error("Row $rowCount: $field exceeds allowed length - $mobile");
        return null; // Return null if the length exceeds the limit
    }

    // Additional sanitization (e.g., remove invalid characters)
    $mobile = preg_replace('/[^\d]/', '', $mobile); // Keep only digits

    return $mobile; // Return sanitized mobile number
}


}
