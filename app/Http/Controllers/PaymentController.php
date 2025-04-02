<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
    public function testEazypayEncryption()
{
    $merchant_id = "141909";
    $aes_key = "1400012719005020";

    // Test Data
    $ref_no = random_int(100000, 999999);
    $sub_merchant_id = "11";
    $amount = "1000";
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
    try {
        // Manually extract and sanitize form-urlencoded keys
        $raw = file_get_contents('php://input');

        // Parse it manually
        parse_str($raw, $rawInput); // turns query string into array

        $response = [
            'response_code'       => $request->get('Response Code'),
            'unique_ref_number'   => $request->get('Unique Ref Number'),
            'transaction_datetime'=> $request->get('Transaction Date'),
            'total_amount'        => $request->get('Total Amount'),
            'interchange_value'   => $request->get('Interchange Value'),
            'tdr'                 => $request->get('TDR'),
            'payment_mode'        => $request->get('Payment Mode'),
            'submerchant_id'      => $request->get('SubMerchantId'),
            'reference_no'        => $request->get('ReferenceNo'),
            'icid'                => $request->get('ID'),
            'rs'                  => $request->get('RS'),
            'tps'                 => $request->get('TPS'),
            'mandatory_fields'    => $request->get('mandatory fields'),
            'optional_fields'     => $request->get('optional fields'),
            'rsv'                 => $request->get('RSV'),
        ];


        // Validate required fields
        if (empty($response['mandatory_fields'])) {
            return response()->json([
                'code' => 400,
                'status' => false,
                'message' => 'Missing required field: mandatory fields',
                'data ' => $response
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

        // Save to `t_pg_responses` table
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
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Payment response saved successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'Failed to save payment response.',
            'error' => $e->getMessage()
        ]);
    }
}
}
