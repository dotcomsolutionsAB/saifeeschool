<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterModel;

class CounterController extends Controller
{
    //
     /**
     * List all counters.
     */
    // public function index()
    // {
    //     try {
    //         $counters = CounterModel::all();
    //         return response()->json(['message' => 'Counters fetched successfully.', 'data' => $counters], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Failed to fetch counters.', 'error' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * Create a new counter.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            't_name' => 'required|string|unique:t_counter|max:255',
            'number' => 'required|numeric'
        ]);

        try {
            $counter = CounterModel::create([
                't_name' => $validated['t_name'],
                // 'number' => 0, // Default value
                'number' => $validated['number']
            ]);

            return response()->json(['message' => 'Counter created successfully.', 'data' => $counter->makeHidden(['id', 'created_at', 'updated_at'])], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create counter.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific and list of all counter.
     */
    public function index(Request $request, $id = null)
    {
        try {
            if ($id) {
                // Fetch the specific counter by ID
                $counter = CounterModel::findOrFail($id);
                return response()->json([
                    'message' => 'Counter fetched successfully.',
                    'data' => $counter->makeHidden(['id', 'created_at', 'updated_at']),
                ], 200);
            } else {
                // Fetch all counters if no ID is provided
                $counters = CounterModel::all();
                return response()->json([
                    'message' => 'All counters fetched successfully.',
                    'data' => $counters->makeHidden(['id', 'created_at', 'updated_at']),
                    'count' => $counters->count(),
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch counters.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update a specific counter.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            't_name' => 'sometimes|string|unique:t_counter,t_name,' . $id . '|max:255',
            'number' => 'sometimes|integer|min:0',
        ]);

        try {
            $counter = CounterModel::findOrFail($id);
            $counter->update($validated);

            return response()->json(['message' => 'Counter updated successfully.', 'data' => $counter->makeHidden(['id', 'created_at', 'updated_at'])], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update counter.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a specific counter.
     */
    public function destroy($id)
    {
        try {
            $counter = CounterModel::findOrFail($id);
            $counter->delete();

            return response()->json(['message' => 'Counter deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete counter.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Increment the counter for a specific table and return the new number.
     */
    public function increment(Request $request)
    {
        $validated = $request->validate([
            't_name' => 'required|string|exists:t_counter,t_name',
        ]);

        try {
            $counter = CounterModel::where('t_name', $validated['t_name'])->firstOrFail();
            $counter->increment('number');

            return response()->json(['message' => 'Counter incremented successfully.', 'data' => $counter], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to increment counter.', 'error' => $e->getMessage()], 500);
        }
    }
}
