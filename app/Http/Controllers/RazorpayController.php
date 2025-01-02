<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// use App\Services\RazorpayService;
use App\Http\Controllers\RazorpayService;

class RazorpayController extends Controller
{
    //

    protected $razorpay;

    public function __construct(RazorpayService $razorpay)
    {
        $this->razorpay = new RazorpayService(); // Instantiate the service manually
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'receipt' => 'nullable|string',
        ]);

        $order = $this->razorpay->createOrderdummy($validated['amount'], 'INR', $validated['receipt'] ?? null);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
        ]);
    }

    // public function createOrder($amount, $currency = 'INR', $receipt = null, $against_fees = null, $st_id = null)
    // {
    //     try {
    //         // Instantiate RazorpayService directly
    //         $razorpayService = new RazorpayService();

    //         $order = $razorpayService->createOrder($amount, $currency, $receipt, $against_fees, $st_id);

    //         return response()->json([
    //             'success' => true,
    //             'order' => $order,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function fetchPaymentStatus($paymentId)
    {
        try {
            $paymentDetails = $this->razorpay->fetchPaymentDetails($paymentId);

            return response()->json($paymentDetails);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchOrderStatus($orderId)
    {
        try {
            $orderDetails = $this->razorpay->fetchOrderDetails($orderId);

             // Log the raw response for debugging
        \Log::info('Fetched Order Details: ', (array) $orderDetails);

            return response()->json($orderDetails);
        } catch (\Exception $e) {

            \Log::error('Error fetching order status: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
