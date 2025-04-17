<?php

namespace App\Http\Controllers;
use App\Models\ItemModel;
use Illuminate\Http\Request;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ItemController extends Controller
{
    //
    puse Illuminate\Support\Facades\Auth;
    use Carbon\Carbon;
    
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'unit' => 'required|string|max:10',
            'price' => 'required|string|max:100',
            'discount' => 'nullable|string|max:100',
            'tax' => 'required|string|max:100',
            'hsn' => 'required|string|max:100',
            // Removed: 'log_user' and 'log_date' validation
        ]);
    
        // Automatically get the current logged-in user's name or ID
        $log_user = Auth::user()->name ?? 'System'; // or use ->id if storing ID
    
        // Automatically get the current date
        $log_date = Carbon::now()->toDateString(); // format: YYYY-MM-DD
    
        $item = ItemModel::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'] ?? '',
            'unit' => $validated['unit'],
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? '',
            'tax' => $validated['tax'],
            'hsn' => $validated['hsn'],
            'log_user' => $log_user,
            'log_date' => $log_date,
        ]);
    
        if ($item) {
            return response()->json([
                'message' => 'Item created successfully.',
                'item' => $item->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);
        } else {
            return response()->json(['message' => 'Failed to create item.'], 500);
        }
    }

    public function index($id = null)
    {
        try {
            if ($id) {
                $item = ItemModel::find($id);
                if ($item) {
                    return response()->json($item->makeHidden(['id', 'created_at', 'updated_at']));
                } else {
                    return response()->json(['message' => 'Item not found.'], 404);
                }
            } else {
                $items = ItemModel::all()->makeHidden(['id', 'created_at', 'updated_at']);
                return response()->json([
                    'item_record' => $items,
                    'count' => $items->count()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching item records.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'unit' => 'required|string|max:10',
            'price' => 'required|string|max:100',
            'discount' => 'nullable|string|max:100',
            'tax' => 'required|string|max:100',
            'hsn' => 'required|string|max:100',
            'log_user' => 'required|string|max:100',
            'log_date' => 'required|date',
        ]);

        $item = ItemModel::find($id);
        if ($item) {
            $item->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'sub_category' => $validated['sub_category'] ?? '',
                'unit' => $validated['unit'],
                'price' => $validated['price'],
                'discount' => $validated['discount'] ?? '',
                'tax' => $validated['tax'],
                'hsn' => $validated['hsn'],
                'log_user' => $validated['log_user'],
                'log_date' => $validated['log_date'],
            ]);
            return response()->json(['message' => 'Item updated successfully.', 'item' => $item->makeHidden(['id', 'created_at', 'updated_at'])]);
        } else {
            return response()->json(['message' => 'Item not found.'], 404);
        }
    }

    public function destroy($id)
    {
        $item = ItemModel::find($id);
        if ($item) {
            $item->delete();
            return response()->json(['message' => 'Item deleted successfully.']);
        } else {
            return response()->json(['message' => 'Item not found.'], 404);
        }
    }

    // csv import
    public function importCsv(Request $request)
    {
        ini_set('max_execution_time', 300); // Extend execution time
        ini_set('memory_limit', '1024M');   // Increase memory limit

        try {
            // Path to the uploaded CSV file
            // $csvFilePath = $request->file('file')->getRealPath();
            $csvFilePath = storage_path('app/public/products.csv');

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
            ItemModel::truncate();

            DB::beginTransaction();

            foreach ($records as $index => $row) {
                try {
                    // Prepare the product data
                    $data[] = [
                        'id' => $row['id'],
                        'name' => $row['name'] ?? '',
                        'description' => $row['description'] ?? '',
                        'category' => $row['category'] ?? '',
                        'sub_category' => $row['sub_category'] ?? '',
                        'unit' => $row['unit'] ?? '',
                        'price' => $row['price'] ?? '0',
                        'discount' => $row['discount'] ?? '0',
                        'tax' => $row['tax'] ?? '0',
                        'hsn' => $row['hsn'] ?? '',
                        'log_user' => $row['log_user'] ?? '',
                        'log_date' => isset($row['log_date']) && strtotime($row['log_date']) ? $row['log_date'] : now()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($data) >= $batchSize) {
                        ItemModel::insert($data);
                        Log::info("Inserted a batch of products.");
                        $data = []; // Reset the batch
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                }
            }

            // Insert remaining records
            if (count($data) > 0) {
                ItemModel::insert($data);
                Log::info("Inserted the remaining products.");
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
