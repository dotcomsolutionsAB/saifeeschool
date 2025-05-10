<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);
        $banks = Bank::orderBy('date', 'desc')->paginate($perPage);
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $banks
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming data
        $data = $request->validate([
            'type' => 'required|in:Deposit,Withdrawal',
            'amount' => 'required|numeric',
            'comments' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Create the Bank record
        $bank = Bank::create($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $bank
        ]);
    }

    public function show($id)
    {
        $bank = Bank::findOrFail($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $bank
        ]);
    }

    public function update(Request $request, $id)
    {
        $bank = Bank::findOrFail($id);
        
        // Validate the incoming data
        $data = $request->validate([
            'type' => 'required|in:Deposit,Withdrawal',
            'amount' => 'required|numeric',
            'comments' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Add the log_user from the authenticated user
        $data['log_user'] = auth()->user()->name;

        // Update the Bank record
        $bank->update($data);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $bank
        ]);
    }

    public function destroy($id)
    {
        Bank::destroy($id);
        return response()->json([
            'code' => 200,
            'success' => true,
            'message' => 'Bank record deleted successfully'
        ]);
    }
}