<?php

namespace App\Http\Controllers;
use App\Models\SuppliersModel;
use Illuminate\Http\Request;

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
}
