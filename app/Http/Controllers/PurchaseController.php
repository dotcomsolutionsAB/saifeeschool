<?php

namespace App\Http\Controllers;
use App\Models\PurchaseModel;
use App\Models\ItemProductModel;
use App\Models\AddonsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class PurchaseController extends Controller
{
    //
    public function register(Request $request)
    {
        $validated = $request->validate([
            'supplier' => 'required|string|max:1000',
            'purchase_invoice_no' => 'required|string|max:100',
            'purchase_invoice_date' => 'required|date',
            'series' => 'required|string|max:100',
            'items' => 'required|array', // Validate items as array
            'items.*.product' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer',
            'items.*.unit' => 'required|string|max:50',
            'items.*.price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric',
            'items.*.hsn' => 'nullable|string|max:50',
            'items.*.tax' => 'nullable|numeric',
            'items.*.cgst' => 'nullable|numeric',
            'items.*.sgst' => 'nullable|numeric',
            'items.*.igst' => 'nullable|numeric',
            'addons' => 'nullable|array', // Validate addons as array
            'addons.freight_value' => 'required|numeric',
            'addons.freight_cgst' => 'nullable|numeric',
            'addons.freight_sgst' => 'nullable|numeric',
            'addons.freight_igst' => 'nullable|numeric',
            'addons.roundoff' => 'required|numeric',
            'currency' => 'required|string|max:100',
            'total' => 'required|numeric',
            'paid' => 'nullable|numeric',
            'cgst' => 'required|numeric',
            'sgst' => 'required|numeric',
            'igst' => 'required|numeric',
            'status' => 'nullable|numeric',
            'log_user' => 'required|string|max:1000',
            'log_date' => 'required|date',
        ]);

        $response = DB::transaction(function () use ($validated) {
            // Create purchase record
            $purchase = PurchaseModel::create([
                'supplier' => $validated['supplier'],
                'purchase_invoice_no' => $validated['purchase_invoice_no'],
                'purchase_invoice_date' => $validated['purchase_invoice_date'],
                'series' => $validated['series'],
                'currency' => $validated['currency'],
                'total' => $validated['total'],
                'paid' => $validated['paid'] ?? 0,
                'cgst' => $validated['cgst'] ?? 0,
                'sgst' => $validated['sgst'] ?? 0,
                'igst' => $validated['igst'] ?? 0,
                'status' => $validated['status'] ?? 0,
                'log_user' => $validated['log_user'],
                'log_date' => $validated['log_date'],
            ]);

            if (!$purchase) {
                return response()->json(['message' => 'Failed to create purchase record.'], 500);
            }

            // Add items to t_purchase_item_products table
            foreach ($validated['items'] as $item) {
                $itemCreated = ItemProductModel::create([
                    'purchase_id' => $purchase->id,
                    'product' => $item['product'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'hsn' => $item['hsn'] ?? null,
                    'tax' => $item['tax'] ?? 0,
                    'cgst' => $item['cgst'] ?? 0,
                    'sgst' => $item['sgst'] ?? 0,
                    'igst' => $item['igst'] ?? 0,
                ]);

                if (!$itemCreated) {
                    return response()->json(['message' => 'Failed to add items to the purchase record.'], 500);
                }
            }

            // Add addons to t_purchase_item_addons table if provided
            if (!empty($validated['addons'])) {
                $addonCreated = AddonsModel::create([
                    'purchase_id' => $purchase->id,
                    'freight_value' => $validated['addons']['freight_value'] ?? null,
                    'freight_cgst' => $validated['addons']['freight_cgst'] ?? null,
                    'freight_sgst' => $validated['addons']['freight_sgst'] ?? null,
                    'freight_igst' => $validated['addons']['freight_igst'] ?? null,
                    'roundoff' => $validated['addons']['roundoff'] ?? null,
                ]);

                if (!$addonCreated) {
                    return response()->json(['message' => 'Failed to add addons to the purchase record.'], 500);
                }
            }

            return response()->json(['message' => 'Purchase record with items and addons created successfully.', 'purchase' => $purchase->makeHidden(['id', 'created_at', 'updated_at'])], 201);
        });

        return $response;
    }


    // public function index($id = null)
    // {
    //     try {
    //         if ($id) {
    //             $purchase = PurchaseModel::find($id);
    //             if ($purchase) {
    //                 return response()->json($purchase->makeHidden(['id', 'created_at', 'updated_at']));
    //             } else {
    //                 return response()->json(['message' => 'Purchase record not found.'], 404);
    //             }
    //         } else {
    //             $purchases = PurchaseModel::all()->makeHidden(['id', 'created_at', 'updated_at']);
    //             return response()->json([
    //                 'purchase_records' => $purchases,
    //                 'count' => $purchases->count()
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while fetching purchase records.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index($id = null)
    {
        try {
            if ($id) {
                $purchase = PurchaseModel::with(['products', 'addons'])->find($id);

                if ($purchase) {
                    return response()->json([
                        'message' => 'Purchase record fetched successfully.',
                        'purchase' => [
                            'details' => $purchase->makeHidden(['id', 'created_at', 'updated_at'])->toArray(),
                            // 'products' => $purchase->products->makeHidden(['id', 'created_at', 'updated_at']),
                            // 'addons' => $purchase->addons->makeHidden(['id', 'created_at', 'updated_at']),
                        ]
                    ]);
                } else {
                    return response()->json(['message' => 'Purchase record not found.'], 404);
                }
            } else {
                $purchases = PurchaseModel::with(['products', 'addons'])->get();

                return response()->json([
                    'message' => 'Purchase records fetched successfully.',
                    'purchase_records' => $purchases->map(function ($purchase) {
                        return [
                            'details' => $purchase->makeHidden(['id', 'created_at', 'updated_at'])->toArray(),
                            // 'products' => $purchase->products->makeHidden(['id', 'created_at', 'updated_at']),
                            // 'addons' => $purchase->addons->makeHidden(['id', 'created_at', 'updated_at']),
                        ];
                    }),
                    'count' => $purchases->count(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching purchase records.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         'supplier' => 'required|string|max:1000',
    //         'purchase_invoice_no' => 'required|string|max:100',
    //         'purchase_invoice_date' => 'required|date',
    //         'series' => 'required|string|max:100',
    //         'items' => 'required|string',
    //         'addons' => 'nullable|string',
    //         'currency' => 'required|string|max:100',
    //         'total' => 'required|string|max:100',
    //         'paid' => 'nullable|string|max:100',
    //         'tax' => 'required|string',
    //         'status' => 'nullable|string|max:100',
    //         'log_user' => 'required|string|max:1000',
    //         'log_date' => 'required|date',
    //     ]);

    //     $purchase = PurchaseModel::find($id);
    //     if ($purchase) {
    //         $purchase->update([
    //             'supplier' => $validated['supplier'],
    //             'purchase_invoice_no' => $validated['purchase_invoice_no'],
    //             'purchase_invoice_date' => $validated['purchase_invoice_date'],
    //             'series' => $validated['series'],
    //             'items' => $validated['items'],
    //             'addons' => $validated['addons'] ?? null,
    //             'currency' => $validated['currency'],
    //             'total' => $validated['total'],
    //             'paid' => $validated['paid'] ?? '0',
    //             'tax' => $validated['tax'],
    //             'status' => $validated['status'] ?? '0',
    //             'log_user' => $validated['log_user'],
    //             'log_date' => $validated['log_date'],
    //         ]);
    //         return response()->json(['message' => 'Purchase record updated successfully.', 'purchase' => $purchase->makeHidden(['id', 'created_at', 'updated_at'])]);
    //     } else {
    //         return response()->json(['message' => 'Purchase record not found.'], 404);
    //     }
    // }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier' => 'required|string|max:1000',
            'purchase_invoice_no' => 'required|string|max:100',
            'purchase_invoice_date' => 'required|date',
            'series' => 'required|string|max:100',
            'items' => 'required|array', // Validate items as array
            'items.*.product' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer',
            'items.*.unit' => 'required|string|max:50',
            'items.*.price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric',
            'items.*.hsn' => 'nullable|string|max:50',
            'items.*.tax' => 'nullable|numeric',
            'items.*.igst' => 'nullable|numeric',
            'addons' => 'nullable|array', // Validate addons as array
            'addons.freight_value' => 'required|numeric',
            'addons.freight_cgst' => 'nullable|numeric',
            'addons.freight_sgst' => 'nullable|numeric',
            'addons.freight_igst' => 'nullable|numeric',
            'addons.roundoff' => 'required|numeric',
            'currency' => 'required|string|max:100',
            'total' => 'required|numeric',
            'paid' => 'nullable|numeric',
            'status' => 'nullable|string|max:100',
            'log_user' => 'required|string|max:1000',
            'log_date' => 'required|date',
        ]);

        $purchase = PurchaseModel::find($id);

        if ($purchase) {
            DB::transaction(function () use ($purchase, $validated) {
                // Update the purchase record
                $purchase->update([
                    'supplier' => $validated['supplier'],
                    'purchase_invoice_no' => $validated['purchase_invoice_no'],
                    'purchase_invoice_date' => $validated['purchase_invoice_date'],
                    'series' => $validated['series'],
                    'currency' => $validated['currency'],
                    'total' => $validated['total'],
                    'paid' => $validated['paid'] ?? '0',
                    'status' => $validated['status'] ?? '0',
                    'log_user' => $validated['log_user'],
                    'log_date' => $validated['log_date'],
                ]);

                // Handle items: update existing, add new, delete old
                $existingProducts = $purchase->products->keyBy('product');
                $updatedProducts = collect($validated['items'])->keyBy('product');

                // Update existing and add new
                foreach ($updatedProducts as $productName => $item) {
                    if ($existingProducts->has($productName)) {
                        // Update existing product
                        $existingProducts[$productName]->update([
                            'description' => $item['description'] ?? null,
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                            'price' => $item['price'],
                            'discount' => $item['discount'] ?? 0,
                            'hsn' => $item['hsn'] ?? null,
                            'tax' => $item['tax'] ?? 0,
                            'igst' => $item['igst'] ?? 0,
                        ]);
                    } else {
                        // Add new product
                        ItemProductModel::create([
                            'purchase_id' => $purchase->id,
                            'product' => $item['product'],
                            'description' => $item['description'] ?? null,
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                            'price' => $item['price'],
                            'discount' => $item['discount'] ?? 0,
                            'hsn' => $item['hsn'] ?? null,
                            'tax' => $item['tax'] ?? 0,
                            'igst' => $item['igst'] ?? 0,
                        ]);
                    }
                }

                // Delete products that are no longer in the updated list
                $productsToDelete = $existingProducts->keys()->diff($updatedProducts->keys());
                ItemProductModel::whereIn('product', $productsToDelete)->where('purchase_id', $purchase->id)->delete();

                // Update addons
                $addon = $purchase->addons->first();
                if ($addon) {
                    $addon->update([
                        'freight_value' => $validated['addons']['freight_value'],
                        'freight_cgst' => $validated['addons']['freight_cgst'] ?? null,
                        'freight_sgst' => $validated['addons']['freight_sgst'] ?? null,
                        'freight_igst' => $validated['addons']['freight_igst'],
                        'roundoff' => $validated['addons']['roundoff'],
                    ]);
                } else {
                    AddonsModel::create([
                        'purchase_id' => $purchase->id,
                        'freight_value' => $validated['addons']['freight_value'],
                        'freight_cgst' => $validated['addons']['freight_cgst'] ?? null,
                        'freight_sgst' => $validated['addons']['freight_sgst'] ?? null,
                        'freight_igst' => $validated['addons']['freight_igst'],
                        'roundoff' => $validated['addons']['roundoff'],
                    ]);
                }
            });

            return response()->json(['message' => 'Purchase record updated successfully.', 'purchase' => $purchase->makeHidden(['id', 'created_at', 'updated_at'])]);
        } else {
            return response()->json(['message' => 'Purchase record not found.'], 404);
        }
    }


    // public function destroy($id)
    // {
    //     $purchase = PurchaseModel::find($id);
    //     if ($purchase) {
    //         $purchase->delete();
    //         return response()->json(['message' => 'Purchase record deleted successfully.']);
    //     } else {
    //         return response()->json(['message' => 'Purchase record not found.'], 404);
    //     }
    // }

    public function destroy($id)
    {
        try {
            // Find the purchase record
            $purchase = PurchaseModel::with(['products', 'addons'])->find($id);

            if (!$purchase) {
                return response()->json(['message' => 'Purchase record not found.'], 404);
            }

            // Use a transaction to ensure all related records are deleted
            DB::transaction(function () use ($purchase) {
                // Delete related products
                $purchase->products()->delete();

                // Delete related addons
                $purchase->addons()->delete();

                // Delete the purchase record itself
                $purchase->delete();
            });

            return response()->json(['message' => 'Purchase record and related data deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the purchase record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function importCsv(Request $request)
    // {
    //     ini_set('max_execution_time', 300); // Extend execution time
    //     ini_set('memory_limit', '1024M');   // Increase memory limit

    //     try {
    //         // Define the path to the CSV file
    //         $csvFilePath = storage_path('app/public/purchase_invoice.csv');

    //         // Check if the file exists
    //         if (!file_exists($csvFilePath)) {
    //             return response()->json(['message' => 'CSV file not found.'], 404);
    //         }

    //         // Read and parse the CSV file
    //         $csvContent = file_get_contents($csvFilePath);
    //         $csv = Reader::createFromString($csvContent);
    //         $csv->setHeaderOffset(0); // Use the first row as the header
    //         $records = (new Statement())->process($csv);

    //         $batchSize = 500; // Number of records to process in one batch
    //         $purchaseData = [];
    //         $itemsData = [];
    //         $addonsData = [];

    //         // Truncate tables before import
    //         PurchaseModel::truncate();
    //         ItemProductModel::truncate();
    //         AddonsModel::truncate();

    //         DB::beginTransaction();
    //         foreach ($records as $index => $row) {
    //             try {
    //                 // Create the purchase data
    //                 $purchase = [
    //                     'supplier' => $row['supplier'],
    //                     'purchase_invoice_no' => $row['purchase_invoice_no'],
    //                     'purchase_invoice_date' => $row['purchase_invoice_date'],
    //                     'series' => $row['series'],
    //                     'currency' => $row['currency'],
    //                     'total' => $row['total'],
    //                     'paid' => $row['paid'],
    //                     'cgst' => $row['cgst'],
    //                     'sgst' => $row['sgst'],
    //                     'igst' => $row['igst'],
    //                     'status' => $row['status'],
    //                     'log_user' => $row['log_user'],
    //                     'log_date' => $row['log_date'],
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];

    //                 // Push the purchase data
    //                 $purchaseData[] = $purchase;

    //                 // Insert purchase data and get the ID
    //                 if (count($purchaseData) >= $batchSize) {
    //                     $insertedPurchases = PurchaseModel::insert($purchaseData);
    //                     $lastPurchaseId = PurchaseModel::latest('id')->first()->id;
    //                     $purchaseData = []; // Reset the batch
    //                 }

    //                 $purchaseId = $lastPurchaseId;

    //                 // Parse and insert items
    //                 if (!empty($row['items'])) {
    //                     $items = json_decode($row['items'], true);
    //                     foreach ($items as $item) {
    //                         $itemsData[] = [
    //                             'purchase_id' => $purchaseId,
    //                             'product' => $item['product'],
    //                             'description' => $item['description'] ?? null,
    //                             'quantity' => $item['quantity'],
    //                             'unit' => $item['unit'],
    //                             'price' => $item['price'],
    //                             'discount' => $item['discount'] ?? 0,
    //                             'hsn' => $item['hsn'] ?? null,
    //                             'tax' => $item['tax'] ?? 0,
    //                             'igst' => $item['igst'] ?? 0,
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ];
    //                     }
    //                 }

    //                 // Parse and insert addons
    //                 if (!empty($row['addons'])) {
    //                     $addons = json_decode($row['addons'], true);
    //                     $addonsData[] = [
    //                         'purchase_id' => $purchaseId,
    //                         'freight_value' => $addons['freight_value'] ?? 0,
    //                         'freight_cgst' => $addons['freight_cgst'] ?? null,
    //                         'freight_sgst' => $addons['freight_sgst'] ?? null,
    //                         'freight_igst' => $addons['freight_igst'] ?? 0,
    //                         'roundoff' => $addons['roundoff'] ?? 0,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];
    //                 }

    //                 // Insert items and addons in batches
    //                 if (count($itemsData) >= $batchSize) {
    //                     ItemProductModel::insert($itemsData);
    //                     $itemsData = []; // Reset the batch
    //                 }

    //                 if (count($addonsData) >= $batchSize) {
    //                     AddonsModel::insert($addonsData);
    //                     $addonsData = []; // Reset the batch
    //                 }
    //             } catch (\Exception $e) {
    //                 Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
    //             }
    //         }

    //         // Insert remaining records
    //         if (count($purchaseData) > 0) {
    //             PurchaseModel::insert($purchaseData);
    //         }

    //         if (count($itemsData) > 0) {
    //             ItemProductModel::insert($itemsData);
    //         }

    //         if (count($addonsData) > 0) {
    //             AddonsModel::insert($addonsData);
    //         }

    //         DB::commit();

    //         return response()->json(['message' => 'CSV imported successfully!'], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Failed to import CSV: ' . $e->getMessage());
    //         return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
    //     }
    // }

    // public function importCsv(Request $request)
    // {
    //     ini_set('max_execution_time', 300); // Extend execution time
    //     ini_set('memory_limit', '1024M');   // Increase memory limit
    
    //     try {
    //         // Define the path to the CSV file
    //         $csvFilePath = storage_path('app/public/purchase_invoice.csv');
    
    //         // Check if the file exists
    //         if (!file_exists($csvFilePath)) {
    //             return response()->json(['message' => 'CSV file not found.'], 404);
    //         }
    
    //         // Read and parse the CSV file
    //         $csvContent = file_get_contents($csvFilePath);
    //         $csv = Reader::createFromString($csvContent);
    //         $csv->setHeaderOffset(0); // Use the first row as the header
    //         $records = (new Statement())->process($csv);
    
    //         $batchSize = 500; // Number of records to process in one batch
    //         $purchaseData = [];
    //         $itemsData = [];
    //         $addonsData = [];
    
    //         // Truncate tables before import
    //         PurchaseModel::truncate();
    //         ItemProductModel::truncate();
    //         AddonsModel::truncate();
    
    //         DB::beginTransaction();
    //         foreach ($records as $index => $row) {
    //             try {
    //                 // Parse the tax field
    //                 $tax = json_decode($row['tax'], true);
    //                 $cgst = $tax['cgst'] ?? 0;
    //                 $sgst = $tax['sgst'] ?? 0;
    //                 $igst = $tax['igst'] ?? 0;
    
    //                 // Create the purchase data
    //                 $purchase = [
    //                     'supplier' => $row['supplier'],
    //                     'purchase_invoice_no' => $row['purchase_invoice_no'],
    //                     'purchase_invoice_date' => $row['purchase_invoice_date'],
    //                     'series' => $row['series'],
    //                     'currency' => $row['currency'],
    //                     'total' => $row['total'],
    //                     'paid' => $row['paid'],
    //                     'cgst' => $cgst,
    //                     'sgst' => $sgst,
    //                     'igst' => $igst,
    //                     'status' => $row['status'],
    //                     'log_user' => $row['log_user'],
    //                     'log_date' => $row['log_date'],
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    
    //                 // Push the purchase data
    //                 $purchaseData[] = $purchase;
    
    //                 // Insert purchase data and get the ID
    //                 if (count($purchaseData) >= $batchSize) {
    //                     $insertedPurchases = PurchaseModel::insert($purchaseData);
    //                     $lastPurchaseId = PurchaseModel::latest('id')->first()->id;
    //                     $purchaseData = []; // Reset the batch
    //                 }
    
    //                 $purchaseId = $lastPurchaseId;
    
    //                 // Parse and insert items
    //                 if (!empty($row['items'])) {
    //                     $items = json_decode($row['items'], true);
    //                     foreach ($items['product'] as $key => $product) {
    //                         $itemsData[] = [
    //                             'purchase_id' => $purchaseId,
    //                             'product' => $product,
    //                             'description' => $items['desc'][$key] ?? null,
    //                             'quantity' => $items['quantity'][$key],
    //                             'unit' => $items['unit'][$key],
    //                             'price' => $items['price'][$key],
    //                             'discount' => $items['discount'][$key] ?? 0,
    //                             'hsn' => $items['hsn'][$key] ?? null,
    //                             'tax' => $items['tax'][$key] ?? 0,
    //                             'igst' => $items['igst'][$key] ?? 0,
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ];
    //                     }
    //                 }
    
    //                 // Parse and insert addons
    //                 if (!empty($row['addons'])) {
    //                     $addons = json_decode($row['addons'], true)['freight'] ?? [];
    //                     $addonsData[] = [
    //                         'purchase_id' => $purchaseId,
    //                         'freight_value' => $addons['value'] ?? 0,
    //                         'freight_cgst' => $addons['cgst'] ?? null,
    //                         'freight_sgst' => $addons['sgst'] ?? null,
    //                         'freight_igst' => $addons['igst'] ?? 0,
    //                         'roundoff' => json_decode($row['addons'], true)['roundoff'] ?? 0,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];
    //                 }
    
    //                 // Insert items and addons in batches
    //                 if (count($itemsData) >= $batchSize) {
    //                     ItemProductModel::insert($itemsData);
    //                     $itemsData = []; // Reset the batch
    //                 }
    
    //                 if (count($addonsData) >= $batchSize) {
    //                     AddonsModel::insert($addonsData);
    //                     $addonsData = []; // Reset the batch
    //                 }
    //             } catch (\Exception $e) {
    //                 Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
    //             }
    //         }
    
    //         // Insert remaining records
    //         if (count($purchaseData) > 0) {
    //             PurchaseModel::insert($purchaseData);
    //         }
    
    //         if (count($itemsData) > 0) {
    //             ItemProductModel::insert($itemsData);
    //         }
    
    //         if (count($addonsData) > 0) {
    //             AddonsModel::insert($addonsData);
    //         }
    
    //         DB::commit();
    
    //         return response()->json(['message' => 'CSV imported successfully!'], 200);
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Failed to import CSV: ' . $e->getMessage());
    //         return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
    //     }
    // }
    
    public function importCsv(Request $request)
{
    ini_set('max_execution_time', 300); // Extend execution time
    ini_set('memory_limit', '1024M');   // Increase memory limit

    try {
        // Define the path to the CSV file
        $csvFilePath = storage_path('app/public/purchase_invoice.csv');

        // Check if the file exists
        if (!file_exists($csvFilePath)) {
            return response()->json(['message' => 'CSV file not found.'], 404);
        }

        // Read and parse the CSV file
        $csvContent = file_get_contents($csvFilePath);
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0); // Use the first row as the header
        $records = (new Statement())->process($csv);

        $batchSize = 500; // Number of records to process in one batch
        $purchaseData = [];
        $itemsData = [];
        $addonsData = [];
        $lastPurchaseId = null;

        // Truncate tables before import
        Log::info('Truncating tables before import...');
        PurchaseModel::truncate();
        ItemProductModel::truncate();
        AddonsModel::truncate();

        Log::info('Starting CSV processing...');
        DB::beginTransaction();
        foreach ($records as $index => $row) {
            try {
                // Log the row being processed
                Log::info('Processing row:', $row);

                // Parse the tax field
                $tax = json_decode($row['tax'], true);
                $cgst = $tax['cgst'] ?? 0;
                $sgst = $tax['sgst'] ?? 0;
                $igst = $tax['igst'] ?? 0;

                // Create the purchase data
                $purchase = [
                    'id' => $row['id'],
                    'supplier' => $row['supplier'],
                    'purchase_invoice_no' => $row['purchase_invoice_no'],
                    'purchase_invoice_date' => $row['purchase_invoice_date'],
                    'series' => $row['series'],
                    'currency' => $row['currency'],
                    'total' => $row['total'],
                    'paid' => $row['paid'],
                    'cgst' => $cgst,
                    'sgst' => $sgst,
                    'igst' => $igst,
                    'status' => $row['status'],
                    'log_user' => $row['log_user'],
                    'log_date' => $row['log_date'],
                    // 'created_at' => now(),
                    // 'updated_at' => now(),
                ];

                // Log purchase data
                Log::info('Prepared purchase data:', $purchase);

                $createdPurchase = PurchaseModel::create($purchase);
                $lastPurchaseId = $createdPurchase->id;

                // Log the purchase ID
                Log::info("Inserted purchase record with ID: $lastPurchaseId");

                // Parse and insert items
                // if (!empty($row['items'])) {
                //     $items = json_decode($row['items'], true);
                //     foreach ($items['product'] as $key => $product) {
                //         $item = [
                //             'purchase_id' => $lastPurchaseId,
                //             'product' => $product,
                //             'description' => $items['desc'][$key] ?? null,
                //             'quantity' => $items['quantity'][$key],
                //             'unit' => $items['unit'][$key],
                //             'price' => $items['price'][$key],
                //             'discount' => $items['discount'][$key] ?? 0,
                //             'hsn' => $items['hsn'][$key] ?? null,
                //             'tax' => $items['tax'][$key] ?? 0,
                //             'igst' => $items['igst'][$key] ?? 0,
                //             'created_at' => now(),
                //             'updated_at' => now(),
                //         ];

                //         // Log item data
                //         Log::info('Prepared item data:', $item);

                //         $itemsData[] = $item;
                //     }
                // }
                // Parse and insert items
                if (!empty($row['items'])) {
                    $items = json_decode($row['items'], true);
                    foreach ($items['product'] as $key => $product) {
                        $item = [
                            'purchase_id' => $lastPurchaseId,
                            'product' => $product,
                            'description' => $items['desc'][$key] ?? null,
                            'quantity' => (int)$items['quantity'][$key],
                            'unit' => $items['unit'][$key],
                            'price' => (float)$items['price'][$key],
                            'discount' => isset($items['discount'][$key]) ? (float)$items['discount'][$key] : 0,
                            'hsn' => $items['hsn'][$key] ?? null,
                            'tax' => isset($items['tax'][$key]) && is_numeric($items['tax'][$key]) ? (float)$items['tax'][$key] : 0, // Default to 0
                            'igst' => isset($items['igst'][$key]) && is_numeric($items['igst'][$key]) ? (float)$items['igst'][$key] : 0, // Default to 0
                            // 'created_at' => now(),
                            // 'updated_at' => now(),
                        ];

                        // Log item data
                        Log::info('Prepared item data:', $item);

                        $itemsData[] = $item;
                    }
                }


                // Insert items
                if (count($itemsData) > 0) {
                    ItemProductModel::insert($itemsData);
                    Log::info('Inserted items for purchase ID:', ['purchase_id' => $lastPurchaseId]);
                    $itemsData = []; // Reset the batch
                }

                // Parse and insert addons
                // if (!empty($row['addons'])) {
                //     $addons = json_decode($row['addons'], true)['freight'] ?? [];
                //     $addon = [
                //         'purchase_id' => $lastPurchaseId,
                //         'freight_value' => $addons['value'] ?? 0,
                //         'freight_cgst' => $addons['cgst'] ?? null,
                //         'freight_sgst' => $addons['sgst'] ?? null,
                //         'freight_igst' => $addons['igst'] ?? 0,
                //         'roundoff' => json_decode($row['addons'], true)['roundoff'] ?? 0,
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];

                //     // Log addon data
                //     Log::info('Prepared addon data:', $addon);

                //     $addonsData[] = $addon;
                // }

                // Parse and insert addons
                if (!empty($row['addons'])) {
                    $addons = json_decode($row['addons'], true)['freight'] ?? [];
                    $addon = [
                        'purchase_id' => $lastPurchaseId,
                        'freight_value' => isset($addons['value']) && is_numeric($addons['value']) ? (float)$addons['value'] : 0, // Default to 0
                        'freight_cgst' => isset($addons['cgst']) && is_numeric($addons['cgst']) ? (float)$addons['cgst'] : 0, // Default to 0
                        'freight_sgst' => isset($addons['sgst']) && is_numeric($addons['sgst']) ? (float)$addons['sgst'] : 0, // Default to 0
                        'freight_igst' => isset($addons['igst']) && is_numeric($addons['igst']) ? (float)$addons['igst'] : 0, // Default to 0
                        'roundoff' => isset($row['addons']['roundoff']) && is_numeric($row['addons']['roundoff']) ? (float)$row['addons']['roundoff'] : 0, // Default to 0
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Log addon data
                    Log::info('Prepared addon data:', $addon);

                    $addonsData[] = $addon;
                }

                // Insert addons
                if (count($addonsData) > 0) {
                    AddonsModel::insert($addonsData);
                    Log::info('Inserted addons for purchase ID:', ['purchase_id' => $lastPurchaseId]);
                    $addonsData = []; // Reset the batch
                }


                // Insert addons
                if (count($addonsData) > 0) {
                    AddonsModel::insert($addonsData);
                    Log::info('Inserted addons for purchase ID:', ['purchase_id' => $lastPurchaseId]);
                    $addonsData = []; // Reset the batch
                }
            } catch (\Exception $e) {
                Log::error('Error processing row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
            }
        }

        DB::commit();

        Log::info('CSV imported successfully!');
        return response()->json(['message' => 'CSV imported successfully!'], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to import CSV: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to import CSV.', 'error' => $e->getMessage()], 500);
    }
}

    
}
