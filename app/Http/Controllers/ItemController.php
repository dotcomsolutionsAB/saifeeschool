<?php

namespace App\Http\Controllers;
use App\Models\ItemModel;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    //
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
            'log_user' => 'required|string|max:100',
            'log_date' => 'required|date',
        ]);

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
            'log_user' => $validated['log_user'],
            'log_date' => $validated['log_date'],
        ]);

        if ($item) {
            return response()->json(['message' => 'Item created successfully.', 'item' => $item->makeHidden(['id', 'created_at', 'updated_at'])], 201);
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

}
