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
        $today = now()->toDateString();

        // Fetch today's transactions and map with Student table

        // DB::enableQueryLog();

        $transactions = PGResponseModel::with('student')
            ->whereDate('transaction_date', $today)
            ->get()
            ->map(function ($transaction, $index) {
                return [
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

        // $queries = DB::getQueryLog();

        // Return success if transactions exist, else return an error message
        return $transactions->count() > 0 
        ? response()->json(['status' => 'success', 'data' => $transactions, 'count' => count($transactions)]) 
        : response()->json(['status' => 'error', 'message' => 'No transactions found for today.']);
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
