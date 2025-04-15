<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\StudentModel;

class PaymentController extends Controller
{
    //
    public function processFeePayment(Request $request)
    {
        try {
            // ✅ Validate request
            $validated = $request->validate([
                'st_id'   => 'required|integer|exists:t_students,id', // Student ID
                'fpp_ids' => 'required|string', // Comma-separated Fee Plan Period IDs
            ]);
    
            $st_id = $validated['st_id'];
            $fpp_ids = explode(',', $validated['fpp_ids']); // Convert to array
    
            // ✅ Fetch Student Wallet Balance
            $student = DB::table('t_students')->where('id', $st_id)->select('st_wallet')->first();
            $wallet_balance = $student->st_wallet ?? 0;
    
            // ✅ Fetch Fee Details
            $fees = DB::table('t_fees')
                ->whereIn('fpp_id', $fpp_ids)
                ->where('st_id', $st_id)
                ->get();
    
            if ($fees->isEmpty()) {
                return response()->json([
                    'code'    => 404,
                    'status'  => false,
                    'message' => 'No valid fees found for this student.',
                ], 404);
            }
    
            // ✅ Calculate Total Payable Amount
            $totalAmount = $fees->sum(function ($fee) {
                $late_fee = (strtotime('today') > strtotime($fee->fpp_due_date)) ? $fee->fpp_late_fee : 0;
                return ($fee->fpp_amount + $late_fee) - ($fee->f_concession ?? 0);
            });
    
            // ✅ Calculate Amount to Pay After Wallet Deduction
            $balance = max(0, $totalAmount - $wallet_balance);
    
            // ✅ Prepare Payment Processing
            $merchant_id = "357605";
            $key = "3508370376005002";
            $ref_no = time() . mt_rand(10000, 99999);
            $return_url = "https://admin.saifeeschool.in/_student/confirmation.php";
            $paymode = "9";
            $man_fields = "{$ref_no}|{$st_id}|{$balance}";
    
            // ✅ Encrypt Data for Payment
            $encryptedData = [
                'sub_merchant_id' => $this->aes128Encrypt($st_id, $key),
                'reference_no'    => $this->aes128Encrypt($ref_no, $key),
                'amount'          => $this->aes128Encrypt($balance, $key),
                'return_url'      => $this->aes128Encrypt($return_url, $key),
                'paymode'         => $this->aes128Encrypt($paymode, $key),
                'mandatory_fields'=> $this->aes128Encrypt($man_fields, $key),
                'optional_fields' => $this->aes128Encrypt("", $key),
            ];
            
    
            // ✅ Insert Payment Log
            DB::table('t_pg_logs')->insert([
                'pg_reference_no' => $ref_no,
                'st_id'           => $st_id,
                'remarks'         => "Fee Payment: " . implode(',', $fpp_ids),
                'f_id'            => implode(',', $fpp_ids),
                'amount'          => $balance,
                'status'          => 'pending',
                'created_at'      => now(),
            ]);
    
            // ✅ Generate Payment URL
            $payment_url = "https://eazypay.icicibank.com/EazyPG?merchantid={$merchant_id}"
                . "&mandatory%20fields={$encryptedData['mandatory_fields']}"
                . "&optional%20fields="
                . "&returnurl={$encryptedData['return_url']}"
                . "&Reference%20No={$encryptedData['reference_no']}"
                . "&submerchantid={$encryptedData['sub_merchant_id']}"
                . "&transaction%20amount={$encryptedData['amount']}"
                . "&paymode={$encryptedData['paymode']}";
    
            // ✅ Response Data
            return response()->json([
                'code'           => 200,
                'status'         => true,
                'message'        => 'Payment details processed successfully.',
                'total_amount'   => number_format($totalAmount, 2),
                'wallet_balance' => number_format($wallet_balance, 2),
                'balance_to_pay' => number_format($balance, 2),
                'payment_url'    => $payment_url, // ✅ Returning only the payment URL
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 500,
                'status'  => false,
                'message' => 'An error occurred while processing payment.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
    private function aes128Encrypt($plaintext, $key)
    {
        return base64_encode(openssl_encrypt($plaintext, "aes-128-ecb", $key, OPENSSL_RAW_DATA));
    }
    public function paymentConfirmation(Request $request)
    {
        // ✅ Get response from EazyPay
        $res = $request->all();
        $referenceno = $request->input('ReferenceNo');

        // ✅ Check if response data exists
        if (!$referenceno) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or missing reference number.',
            ], 400);
        }

        // ✅ Fetch transaction log from database
        $transaction = DB::table('t_pg_logs')->where('pg_reference_no', $referenceno)->first();

        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        // ✅ Determine the type (1 = Admin, else Student)
        $type = $transaction->type ?? 0;

        // ✅ Load appropriate confirmation process
        if ($type == 1) {
            return $this->adminConfirmation($transaction);
        } else {
            return $this->studentConfirmation($transaction);
        }
    }

    private function adminConfirmation($transaction)
    {
        // ✅ Logic for admin payment confirmation
        return response()->json([
            'status' => true,
            'message' => 'Admin payment confirmed.',
            'transaction' => $transaction,
        ]);
    }

    private function studentConfirmation($transaction)
    {
        // ✅ Logic for student payment confirmation
        return response()->json([
            'status' => true,
            'message' => 'Student payment confirmed.',
            'transaction' => $transaction,
        ]);
    }
    public function testEazypayEncryption(Request $request)
{
    $merchant_id = "141909";
    $aes_key = "1400012719005020";
    $validated = $request->validate([
        'st_id'   => 'required|integer|exists:t_students,id', // Student ID
        'fpp_ids' => 'required|string', // Comma-separated Fee Plan Period IDs
    ]);

    $st_id = $validated['st_id'];
    $fpp_ids = explode(',', $validated['fpp_ids']); // Convert to array

    // ✅ Fetch Student Wallet Balance
    $student = DB::table('t_students')->where('id', $st_id)->select('st_wallet')->first();
    $wallet_balance = $student->st_wallet ?? 0;

    // ✅ Fetch Fee Details
    $fees = DB::table('t_fees')
        ->whereIn('fpp_id', $fpp_ids)
        ->where('st_id', $st_id)
        ->get();

    if ($fees->isEmpty()) {
        return response()->json([
            'code'    => 404,
            'status'  => false,
            'message' => 'No valid fees found for this student.',
        ], 404);
    }

    // ✅ Calculate Total Payable Amount
    $totalAmount = $fees->sum(function ($fee) {
        $late_fee = (strtotime('today') > strtotime($fee->fpp_due_date)) ? $fee->fpp_late_fee : 0;
        return ($fee->fpp_amount + $late_fee) - ($fee->f_concession ?? 0);
    });

    // ✅ Calculate Amount to Pay After Wallet Deduction
    $balance = max(0, $totalAmount - $wallet_balance);


    // Test Data
    $ref_no = time() . mt_rand(10000, 99999);
    $sub_merchant_id = "11";
    $amount = $balance;
    $return_url = "https://saifeeschool.dotcombusiness.in/api/fee/confirmation";
    $paymode = "9";
    $mandatory_fields = "{$ref_no}|{$sub_merchant_id}|{$amount}";
    $optional_fields = "";

    // Encrypt Each Parameter
    $encrypt = function ($value) use ($aes_key) {
        return base64_encode(openssl_encrypt($value, "aes-128-ecb", $aes_key, OPENSSL_RAW_DATA));
    };

    $encrypted = [
        'mandatory_fields' => $encrypt($mandatory_fields),
        'optional_fields'  => $encrypt($optional_fields),
        'return_url'       => $encrypt($return_url),
        'reference_no'     => $encrypt($ref_no),
        'sub_merchant_id'  => $encrypt($sub_merchant_id),
        'transaction_amount' => $encrypt($amount),
        'paymode'          => $encrypt($paymode),
    ];
    DB::table('t_pg_logs')->insert([
        'pg_reference_no' => $ref_no,
        'st_id'           => $st_id,
        'remarks'         => "Fee Payment: " . implode(',', $fpp_ids),
        'f_id'            => implode(',', $fpp_ids),
        'amount'          => $balance,
        'status'          => 'pending',
        'created_at'      => now(),
    ]);

    // Assemble URL
    $payment_url = "https://eazypayuat.icicibank.com/EazyPG?merchantid={$merchant_id}"
        . "&mandatory fields={$encrypted['mandatory_fields']}"
        . "&optional fields={$encrypted['optional_fields']}"
        . "&returnurl={$encrypted['return_url']}"
        . "&Reference No={$encrypted['reference_no']}"
        . "&submerchantid={$encrypted['sub_merchant_id']}"
        . "&transaction amount={$encrypted['transaction_amount']}"
        . "&paymode={$encrypted['paymode']}";

    // Return for Debugging
    return response()->json([
        'code' => 200,
        'status' => true,
        'message' => 'Test EazyPay Encrypted URL Generated',
        'url' => $payment_url,
        'encrypted_values' => $encrypted
    ]);
}
public function feeConfirmation(Request $request)
{
    //echo " Hello";
    try {
        $raw = file_get_contents('php://input');
        $parsed = [];
        
        // Manually parse the data
        parse_str($raw, $parsed);

        // Use parsed data to create response array
        $response = [
            'response_code'       => $parsed['Response_Code'] ?? null,
            'unique_ref_number'   => $parsed['Unique_Ref_Number'] ?? null,
            'transaction_datetime'=> $parsed['Transaction_Date'] ?? null,
            'total_amount'        => $parsed['Total_Amount'] ?? null,
            'interchange_value'   => $parsed['Interchange_Value'] ?? null,
            'tdr'                 => $parsed['TDR'] ?? null,
            'payment_mode'        => $parsed['Payment_Mode'] ?? null,
            'submerchant_id'      => $parsed['SubMerchantId'] ?? null,
            'reference_no'        => $parsed['ReferenceNo'] ?? null,
            'icid'                => $parsed['ID'] ?? null,
            'rs'                  => $parsed['RS'] ?? null,
            'tps'                 => $parsed['TPS'] ?? null,
            'mandatory_fields'    => $parsed['mandatory_fields'] ?? null,
            'optional_fields'     => $parsed['optional_fields'] ?? null,
            'rsv'                 => $parsed['RSV'] ?? null,
        ];

        // Validate required fields
        if (empty($response['mandatory_fields'])) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'Missing required field: mandatory fields',
                'data' => $response
            ]);
        }

        // Parse the transaction date (dd-mm-yyyy hh:ii:ss to Y-m-d H:i:s)
        $response['transaction_date'] = null;
        $response['transaction_time'] = null;
        if (!empty($response['transaction_datetime'])) {
            $dt = \DateTime::createFromFormat('d-m-Y H:i:s', $response['transaction_datetime']);
            if ($dt) {
                $response['transaction_date'] = $dt->format('Y-m-d');
                $response['transaction_time'] = $dt->format('H:i:s');
            }
        }

        // Remove unneeded key before insert
        unset($response['transaction_datetime']);

        // Use the mapResponseCode function to get status and description based on response_code
        $responseCodeDetails = $this->mapResponseCode($response['response_code']);
        
        // Add response status and description to the response
        $response['status'] = $responseCodeDetails['status'];
        $response['desc'] = $responseCodeDetails['desc'];

        // Save the response to `t_pg_responses` table
        DB::table('t_pg_responses')->insert([
            'response_code'     => $response['response_code'],
            'unique_ref_number' => $response['unique_ref_number'],
            'transaction_date'  => $response['transaction_date'],
            'transaction_time'  => $response['transaction_time'],
            'total_amount'      => $response['total_amount'],
            'interchange_value' => $response['interchange_value'],
            'tdr'               => $response['tdr'],
            'payment_mode'      => $response['payment_mode'],
            'submerchant_id'    => $response['submerchant_id'],
            'reference_no'      => $response['reference_no'],
            'icid'              => $response['icid'],
            'rs'                => $response['rs'],
            'tps'               => $response['tps'],
            'mandatory_fields'  => $response['mandatory_fields'],
            'optional_fields'   => $response['optional_fields'],
            'rsv'               => $response['rsv'],
            //'status'            => $response['status'],
            //'desc'              => $response['desc'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if ($response['response_code'] === 'E000') {
            echo " hello";
            // If the response code is 'E000' (success), call the processPaymentDetails method
            $this->processPaymentDetails($parsed);
        }

        // Return the response with status and description based on response_code..
        return response()->json([
            'code' => 200,
            'status' => true,
            'Payment_Sucess'=>$response['response_code']=='E000'?true:false,
            'message' => $response['desc'],  // This gives the description of the response code
            
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'Failed to save payment response.',
            'error' => $e->getMessage(),
            'data' => $response
        ]);
    }
}
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
public function processPaymentDetails($parsed)
{
    try {
        // Match the reference_id from the t_pg_logs table to get st_id and fpp_ids
        $pgLog = DB::table('t_pg_logs')->where('pg_reference_no', $parsed['ReferenceNo'])->first();

        if (!$pgLog) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'No matching payment reference found.',
            ]);
        }

        $st_id = $pgLog->st_id;  // Get student ID
        $fpp_ids = explode(',', $pgLog->f_id);  // Get fee IDs, assuming they are comma-separated

        // Get the student's current wallet balance
        $student = StudentModel::findOrFail($st_id);
        $walletBalance = $student->st_wallet;

        // Calculate the total amount to be added to the wallet
        $txnAmount = $parsed['Total_Amount'];
        $newWalletBalance = $walletBalance + $txnAmount;

        // Update the student's wallet balance
        $student->st_wallet = $newWalletBalance;
        $student->save();

        // Add a transaction for the wallet deposit (txn_type = 1 for adding money)
        DB::table('t_txns')->insert([
            'st_id' => $st_id,
            'sch_id' => 1,  // Assuming school ID is 1
            'txn_type_id' => 1,  // Transaction type for adding money
            'txn_date' => now()->toDateString(),
            'txn_time' => now()->toTimeString(),
            'txn_mode' => 'internal',  // Payment mode
            'txn_amount' => $txnAmount,
            'f_id' => null,  // No fee ID for wallet deposit
            'f_normal' => 0,
            'f_late' => 0,
            'txn_tagged_to_id' => null,  // You can fill this if necessary
            'txn_reason' => 'Wallet deposit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Process transactions for each fee payment (fpp_id)
        foreach ($fpp_ids as $fpp_id) {
            // Retrieve the fee data based on fpp_id
            $fee = DB::table('t_fees')
                    ->where('fpp_id', $fpp_id)
                    ->where('st_id', $st_id)
                    ->first();

            // Proceed if the fee exists
            if ($fee) {
                // Determine the transaction type (normal or late fee)
                $txnTypeId = ($fee->f_late_fee_applicable == 1) ? 3 : 2;  // If late fee applicable, txn_type = 3

                // Add a transaction entry for the fee payment (normal fee)
                DB::table('t_txns')->insert([
                    'st_id' => $st_id,
                    'sch_id' => 1,  // Assuming school ID is 1
                    'txn_type_id' => 2,  // Transaction type for normal fee
                    'txn_date' => now()->toDateString(),
                    'txn_time' => now()->toTimeString(),
                    'txn_mode' => 'internal',  // Payment mode
                    'txn_amount' => $fee->fpp_amount,
                    'f_id' => $fpp_id,
                    'f_normal' => 1,
                    'f_late' => 0,
                    'txn_tagged_to_id' => null,
                    'txn_reason' => 'Fee Payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // If late fee is applicable, create another transaction for the late fee
                if ($fee->f_late_fee_applicable == 1) {
                    DB::table('t_txns')->insert([
                        'st_id' => $st_id,
                        'sch_id' => 1,  // Assuming school ID is 1
                        'txn_type_id' => 3,  // Transaction type for late fee
                        'txn_date' => now()->toDateString(),
                        'txn_time' => now()->toTimeString(),
                        'txn_mode' => 'internal',  // Payment mode
                        'txn_amount' => $fee->f_late_fee,  // Only the late fee amount
                        'f_id' => $fpp_id,
                        'f_normal' => 0,
                        'f_late' => 1,
                        'txn_tagged_to_id' => null,
                        'txn_reason' => 'Late Fee Payment',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Deduct wallet balance after the transaction
                $newWalletBalanceAfterPayment = $newWalletBalance - $fee->fpp_amount - ($fee->f_late_fee_applicable ? $fee->f_late_fee : 0);
                $student->st_wallet = $newWalletBalanceAfterPayment;
                $student->save();

                // Mark fee as paid
                DB::table('t_fees')->where('id', $fee->id)->update(['f_paid' => 1]);
            }
        }

        $pgLog = DB::table('t_pg_logs')->where('pg_reference_no', $parsed['ReferenceNo'])->first();

if ($pgLog) {
    // Process the payment logic (wallet addition, fee payment, etc.)
    // Your payment processing code here...

    // Update the payment status to 'completed'
    DB::table('t_pg_logs')
        ->where('pg_reference_no', $parsed['ReferenceNo'])
        ->update(['status' => 'completed', 'updated_at' => now()]);
}

        // Response with success message
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Payment processed successfully, wallet updated, and fees paid.',
        ]);
    } catch (\Exception $e) {
        // Handle any exceptions during the process
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'An error occurred while processing payment.',
            'error' => $e->getMessage(),
        ]);
    }
}
}
