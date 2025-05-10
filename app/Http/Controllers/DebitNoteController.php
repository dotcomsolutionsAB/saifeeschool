<?php

namespace App\Http\Controllers;




use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DebitNote;

class DebitNoteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);
        $debitNotes = DebitNote::orderBy('date', 'desc')->paginate($perPage);
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $debitNotes
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming data
        $data = $request->validate([
            'debit_no' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'paid_to' => 'required|string',
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Create the DebitNote record
        $debit = DebitNote::create($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $debit
        ]);
    }

    public function show($id)
    {
        $debit = DebitNote::findOrFail($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $debit
        ]);
    }

    public function update(Request $request, $id)
    {
        $debit = DebitNote::findOrFail($id);

        // Validate the incoming data
        $data = $request->validate([
            'debit_no' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'paid_to' => 'required|string',
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Update the DebitNote record
        $debit->update($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $debit
        ]);
    }

    public function destroy($id)
    {
        DebitNote::destroy($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'Debit Note deleted successfully'
        ]);
    }
}