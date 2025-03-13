<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $return_url = "https://saifeeschool.dotcombusiness.in/api/payment/confirmation";
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
}
