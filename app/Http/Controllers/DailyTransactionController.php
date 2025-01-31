<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PGResponseModel;
use App\Exports\DailyTransactionExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class DailyTransactionController extends Controller
{
    //
    /**
     * Fetch and display daily transactions.
     */
    public function index(Request $request)
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'search' => 'nullable|string|max:255', // Search term (Name, Roll No, ID, Reference No)
                'mode' => 'nullable|string|max:255', // Payment Mode filter
                'date_from' => 'nullable|date', // Date from filter
                'date_to' => 'nullable|date|after_or_equal:date_from', // Date to filter
                'offset' => 'nullable|integer|min:0', // Pagination offset
                'limit' => 'nullable|integer|min:1|max:100', // Limit (default 10, max 100)
            ]);
    
            // Set default pagination values
            $offset = $validated['offset'] ?? 0;
            $limit = $validated['limit'] ?? 10;
    
            // Start query with relationships
            $query = PGResponseModel::with(['student'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('transaction_time', 'desc');
    
            // Apply search filters (search in Name, Roll No, ID, Reference No)
            if (!empty($validated['search'])) {
                $searchTerm = '%' . trim($validated['search']) . '%';
    
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->whereHas('student', function ($studentQuery) use ($searchTerm) {
                        $studentQuery->whereRaw("LOWER(st_first_name) LIKE ?", [strtolower($searchTerm)])
                            ->orWhereRaw("LOWER(st_last_name) LIKE ?", [strtolower($searchTerm)])
                            ->orWhere('st_roll_no', 'like', $searchTerm);
                    })->orWhere('reference_no', 'like', $searchTerm);
                });
            }
    
            // Apply payment mode filter
            if (!empty($validated['mode'])) {
                $query->where('payment_mode', $validated['mode']);
            }
    
            // Apply date filters
            if (!empty($validated['date_from'])) {
                $query->whereDate('transaction_date', '>=', $validated['date_from']);
            }
            if (!empty($validated['date_to'])) {
                $query->whereDate('transaction_date', '<=', $validated['date_to']);
            }
    
            // Get total count for pagination
            $totalCount = $query->count();
    
            // Fetch paginated results
            $transactions = $query->offset($offset)->limit($limit)->get();
    
            // Calculate total amount for the current page
            $totalAmountForPage = $transactions->sum('total_amount');
    
            // Map data
            $formattedTransactions = $transactions->map(function ($transaction, $index) use ($offset) {
                $student = $transaction->student;
    
                return [
                    'SN' => $offset + $index + 1,
                    'Name' => $student ? "{$student->st_first_name} {$student->st_last_name}" : 'N/A',
                    'Roll No' => $student ? $student->st_roll_no : 'N/A',
                    'Date' => "{$transaction->transaction_date} {$transaction->transaction_time}",
                    'Unique_Ref_No' => $transaction->unique_ref_number,
                    'Total_Amount' => $transaction->total_amount,
                    'Status' => $transaction->response_code === 'E000' ? 'Success received successfully' : 'Pending',
                    'Mode' => $transaction->payment_mode,
                ];
            });
    
            // Return response with paginated data
            return $formattedTransactions->count() > 0 
                ? response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Transactions fetched successfully.',
                    'data' => $formattedTransactions,
                    'total' => $totalCount, // Total transactions matching the criteria
                    'offset' => $offset,
                    'limit' => $limit,
                    'page_total_amount' => $totalAmountForPage, // Total amount for the current page of results
                ])
                : response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'No transactions found for the given criteria.',
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'An error occurred while fetching transactions.',
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function getDistinctPaymentModes()
{
    try {
        // Fetch distinct payment modes from the table
        $paymentModes = PGResponseModel::distinct()->pluck('payment_mode')->filter()->values();

        // Check if any payment modes exist
        if ($paymentModes->isEmpty()) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No payment modes found.',
            ]);
        }

        // Return response
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Payment modes fetched successfully.',
            'data' => $paymentModes,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while fetching payment modes.',
            'error' => $e->getMessage(),
        ]);
    }
}
    
    /**
     * Export transactions to Excel.
     */
    public function exportToExcel()
    {
        $today = now()->toDateString();

        // Fetch today's transactions and map with Student table
        $transactions = PGResponseModel::with('student')
            ->whereDate('transaction_date', $today)
            ->get()
            ->map(function ($transaction, $index) {
                return [
                    // 'SN' => $transaction->id,
                    'SN' =>  $index + 1,
                    'Name' => $transaction->student
                    ? $transaction->student->st_first_name . ' ' . $transaction->student->st_last_name
                    : 'N/A',
                    'Roll No' => $transaction->student ? $transaction->student->st_roll_no : 'N/A',
                    'Date' => "{$transaction->transaction_date} {$transaction->transaction_time}",
                    'Unique_Ref_No' => $transaction->unique_ref_number,
                    'Total_Amount' => $transaction->total_amount,
                    'Status' => $this->mapResponseCode($transaction->response_code)['status'],
                    'Mode' => $transaction->payment_mode,
                ];
            });

        return Excel::download(new DailyTransactionExport($transactions), 'daily_transactions.xlsx');
    }

    /**
     * Map response code to status and description.
     */
    private function mapResponseCode($code)
    {
        switch ($code) {
            case 'E000':
                return ['status' => 'Success', 'desc' => 'Received successfully.'];
            case 'E008':
                return ['status' => 'Failure', 'desc' => 'Failure from Third Party due to Technical Error.'];
            case 'E0803':
                return ['status' => 'Failure', 'desc' => 'Canceled by user.'];
            case 'E0823':
                return ['status' => 'Failure', 'desc' => 'Invalid 3D Secure values.'];
            case 'E0812':
                return ['status' => 'Failure', 'desc' => 'Do not honor.'];
            case 'E0830':
                return ['status' => 'Failure', 'desc' => 'Issuer or switch is inoperative.'];
            case 'E0801':
                return ['status' => 'Failure', 'desc' => 'FAIL.'];
            case 'E0805':
                return ['status' => 'Failure', 'desc' => 'Checkout page rendered Card function not supported.'];
            case 'E0832':
                return ['status' => 'Failure', 'desc' => 'Restricted card.'];
            case 'E0035':
                return ['status' => 'Failure', 'desc' => 'Sub merchant id coming from merchant is empty.'];
            case 'E0820':
                return ['status' => 'Failure', 'desc' => 'ECI 1 and ECI6 Error for Debit Cards and Credit Cards.'];
            case 'E006':
                return ['status' => 'Failure', 'desc' => 'Transaction is already paid.'];
            case 'E0807':
                return ['status' => 'Failure', 'desc' => 'PG Fwd Fail / Issuer Authentication Server failure.'];
            case 'E00335':
                return ['status' => 'Failure', 'desc' => 'Transaction Cancelled By User.'];
            case 'E0821':
                return ['status' => 'Failure', 'desc' => 'ECI 7 for Debit Cards and Credit Cards.'];
            case 'E0816':
                return ['status' => 'Failure', 'desc' => 'No Match with the card number.'];
            case 'E0842':
                return ['status' => 'Failure', 'desc' => 'Invalid expiration date.'];
            case 'E0841':
                return ['status' => 'Failure', 'desc' => 'SYSTEM ERROR.'];
            case 'E0824':
                return ['status' => 'Failure', 'desc' => 'Bad Track Data.'];
            default:
                return ['status' => 'Failure', 'desc' => '---'];
        }
    }
}
