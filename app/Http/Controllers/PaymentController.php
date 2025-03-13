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
        $totalAmount = 0;
        foreach ($fees as $fee) {
            $late_fee = (strtotime('today') > strtotime($fee->fpp_due_date)) ? $fee->fpp_late_fee : 0;
            $totalAmount += ($fee->fpp_amount + $late_fee) - ($fee->f_concession ?? 0);
        }

        // ✅ Calculate Amount to Pay After Wallet Deduction
        $balance = max(0, $totalAmount - $wallet_balance);

        // ✅ Prepare Payment Processing
        $merchant_id = "357605";
        $key = "3508370376005002";
        $ref_no = time() . mt_rand(10000, 99999);
        $return_url = "https://admin.saifeeschool.in/_student/confirmation.php";
        $paymode = "9";
        $man_fields = $ref_no . "|" . $st_id . "|" . $balance;

        // ✅ Encrypt Data for Payment
        $e_sub_mer_id = $this->aes128Encrypt($st_id, $key);
        $e_ref_no = $this->aes128Encrypt($ref_no, $key);
        $e_amt = $this->aes128Encrypt($balance, $key);
        $e_return_url = $this->aes128Encrypt($return_url, $key);
        $e_paymode = $this->aes128Encrypt($paymode, $key);
        $e_man_fields = $this->aes128Encrypt($man_fields, $key);

        // ✅ Insert Payment Log
        DB::table('pg_logs_bk')->insert([
            'pg_reference_no'  => $ref_no,
            'pg_sub_merchant'  => $st_id,
            'ref_no'           => $st_id,
            'remarks'          => "Fee Payment: " . implode(',', $fpp_ids),
            'f_id'             => implode(',', $fpp_ids),
            'timestamp'        => now(),
        ]);

        // ✅ Generate Payment URL
        $payment_url = "https://eazypay.icicibank.com/EazyPG?merchantid=$merchant_id&mandatory fields=$e_man_fields&optional fields=&returnurl=$e_return_url&Reference No=$e_ref_no&submerchantid=$e_sub_mer_id&transaction amount=$e_amt&paymode=$e_paymode";

        // ✅ Response Data
        return response()->json([
            'code'           => 200,
            'status'         => true,
            'message'        => 'Payment details processed successfully.',
            'total_amount'   => number_format($totalAmount, 2),
            'wallet_balance' => number_format($wallet_balance, 2),
            'balance_to_pay' => number_format($balance, 2),
            'payment_url'    => $payment_url, // ✅ Only returning the payment URL
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
private function aes128Encrypt($plaintext, $key) {
    $cipher = "aes-128-ecb"; // Ensure lowercase
    return base64_encode(openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA));
}
}
