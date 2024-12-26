<?php

namespace App\Http\Controllers;
use App\Models\SuppliersModel;
use Illuminate\Http\Request;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    //
    public function register(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:100',
            'name' => 'required|string|max:110',
            'address' => 'required|string|max:256',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'mobile' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'documents' => 'nullable|string',
            'bank_details' => 'required|string',
            'notes' => 'nullable|string',
            'gstin' => 'required|string|max:15',
            'gstin_type' => 'required|string|max:100',
            'notification' => 'nullable|string|max:256',
            'log_user' => 'required|string|max:100',
            'log_date' => 'required|date',
        ]);

        $supplier = SuppliersModel::create([
            'company' => $validated['company'],
            'name' => $validated['name'],
            'address' => $validated['address'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'documents' => $validated['documents'] ?? null,
            'bank_details' => $validated['bank_details'],
            'notes' => $validated['notes'] ?? null,
            'gstin' => $validated['gstin'],
            'gstin_type' => $validated['gstin_type'],
            'notification' => $validated['notification'] ?? null,
            'log_user' => $validated['log_user'],
            'log_date' => $validated['log_date'],
        ]);

        if ($supplier) {
            return response()->json(['message' => 'Suppliers created successfully.', 'company' => $supplier->makeHidden(['id', 'created_at', 'updated_at'])], 201);
        } else {
            return response()->json(['message' => 'Failed to create company.'], 500);
        }
    }

    public function index($id = null)
    {
        try {
            if ($id) {
                $supplier = SuppliersModel::find($id);
                if ($supplier) {
                    return response()->json($supplier->makeHidden(['id', 'created_at', 'updated_at']));
                } else {
                    return response()->json(['message' => 'Supplier not found.'], 404);
                }
            } else {
                $suppliers = SuppliersModel::all()->makeHidden(['id', 'created_at', 'updated_at']);
                return response()->json([
                    'supplier_record' => $suppliers,
                    'count' => $suppliers->count()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching supplier records.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:100',
            'name' => 'required|string|max:110',
            'address' => 'required|string|max:256',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'mobile' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'documents' => 'nullable|string',
            'bank_details' => 'required|string',
            'notes' => 'nullable|string',
            'gstin' => 'required|string|max:15',
            'gstin_type' => 'required|string|max:100',
            'notification' => 'nullable|string|max:256',
            'log_user' => 'required|string|max:100',
            'log_date' => 'required|date',
        ]);

        $supplier = SuppliersModel::find($id);
        if ($supplier) {
            $supplier->update([
                'company' => $validated['company'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'state' => $validated['state'],
                'country' => $validated['country'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'],
                'documents' => $validated['documents'] ?? null,
                'bank_details' => $validated['bank_details'],
                'notes' => $validated['notes'] ?? null,
                'gstin' => $validated['gstin'],
                'gstin_type' => $validated['gstin_type'],
                'notification' => $validated['notification'] ?? null,
                'log_user' => $validated['log_user'],
                'log_date' => $validated['log_date'],
            ]);
            return response()->json(['message' => 'Supplier updated successfully.', 'supplier' => $supplier->makeHidden(['id', 'created_at', 'updated_at'])]);
        } else {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }
    }

    public function destroy($id)
    {
        $supplier = SuppliersModel::find($id);
        if ($supplier) {
            $supplier->delete();
            return response()->json(['message' => 'Supplier deleted successfully.']);
        } else {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }
    }

    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // Path to the uploaded CSV file
            // $csvFilePath = $request->file('file')->getRealPath();
            $csvFilePath = storage_path('app/public/suppliers.csv');

            // Check if the file exists
            if (!file_exists($csvFilePath)) {
                return response()->json(['message' => 'CSV file not found.'], 404);
            }

            // Read the CSV file
            $csvContent = file_get_contents($csvFilePath);
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0); // Use the first row as the header
            $records = (new Statement())->process($csv);

            $batchSize = 500; // Number of records to process in one batch
            $data = [];

            // Truncate the table before import (optional)
            SuppliersModel::truncate();

            DB::beginTransaction();

            foreach ($records as $index => $row) {
                try {
                    // Parse address JSON
                    $address = json_decode($row['address'], true);

                    $data[] = [
                        'id' => $row['id'],
                        'company' => $row['company'] ?? '',
                        'name' => $row['name'] ?? '',
                        'address1' => $address['address1'] ?? '',
                        'address2' => $address['address2'] ?? null,
                        'city' => $address['city'] ?? '',
                        'pincode' => is_numeric($address['pincode']) ? $address['pincode'] : null,
                        'state' => $row['state'] ?? '',
                        'country' => $row['country'] ?? '',
                        'mobile' => $row['mobile'] ?? '',
                        'email' => $row['email'] ?? '',
                        'documents' => $row['documents'] ?? null,
                        'notes' => $row['notes'] ?? null,
                        'gstin' => $row['gstin'] ?? '',
                        'gstin_type' => $row['gstin_type'] ?? '',
                        'notification' => $row['notification'] ?? null,
                        'log_user' => $row['log_user'] ?? '',
                        'log_date' => isset($row['log_date']) && strtotime($row['log_date']) ? $row['log_date'] : now()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        SuppliersModel::insert($data);
                        Log::info("Inserted a batch of suppliers.");
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                SuppliersModel::insert($data);
                Log::info("Inserted the remaining suppliers.");
            }

            DB::commit();

            return response()->json(['message' => 'CSV imported successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
        }
    }
}
