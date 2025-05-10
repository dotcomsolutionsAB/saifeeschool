<?php

namespace App\Http\Controllers;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreditNote;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);
        $creditNotes = CreditNote::orderBy('date', 'desc');
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $creditNotes
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming data
        $data = $request->validate([
            'credit_no' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'collected_from' => 'required|string',
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Create the CreditNote record
        $credit = CreditNote::create($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $credit
        ]);
    }

    public function show($id)
    {
        $credit = CreditNote::findOrFail($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $credit
        ]);
    }

    public function update(Request $request, $id)
    {
        $credit = CreditNote::findOrFail($id);

        // Validate the incoming data
        $data = $request->validate([
            'credit_no' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'collected_from' => 'required|string',
            'cheque_no' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Update the CreditNote record
        $credit->update($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $credit
        ]);
    }

    public function destroy($id)
    {
        CreditNote::destroy($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'Credit Note deleted successfully'
        ]);
    }
}