<?php

namespace  App\Http\Controllers;

use Razorpay\Api\Api;
use App\Models\PaymentFeesModel;

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    public function createOrderdummy($amount, $currency = 'INR', $receipt = null)
    {
        return $this->api->order->create([
            'amount' => $amount * 100, // Amount in paise
            'currency' => $currency,
            'receipt' => $receipt,
            'payment_capture' => 1, // Auto-capture payment
        ]);
    }

    public function createOrder($amount, $currency = 'INR', $receipt = null, $against_fees = null, $st_id = null)
    {
        // Create order with Razorpay API
        $order = $this->api->order->create([
            'amount' => $amount * 100, // Amount in paise
            'currency' => $currency,
            'receipt' => $receipt,
            'payment_capture' => 1, // Auto-capture payment
        ]);

        // Save order details in the database
        PaymentFeesModel::create([
            'against_fees' => $against_fees,
            'order_id' => $order['id'],
            'st_id' => $st_id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $order['status'],
        ]);

        return $order->toArray();
    }


    public function fetchPaymentDetails($paymentId)
    {
        return $this->api->payment->fetch($paymentId);
    }

    public function fetchOrderDetails($orderId)
    {
        // return $this->api->order->fetch($orderId);

        $orderDetails = $this->api->order->fetch($orderId);

        return $orderDetails->toArray(); // Convert Razorpay object to an array
    }
}
