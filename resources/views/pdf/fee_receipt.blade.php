<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt</title>
    <style>
        @page { size: A5 landscape; margin: 0; }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-image: url('{{ storage_path("app/public/pdf/sgjeps_receipt.jpg") }}'); 
            background-size: cover;
            width: 210mm; 
            height: 148mm; 
            position: relative; 
        }
        .container { position: absolute; width: 100%; height: 100%; }
        .field { position: absolute; font-size: 36px; font-weight: bold; }

        /* Positioning Each Field Based on FPDF setXY */
        .receipt-no { top: 5mm; left: 20mm; font-size: 48px} /* Receipt ID */
        .date { top: 45mm; left: 160mm;font-size: 48px } /* Date */

        .name { top: 56mm; left: 75mm; font-size: 48px} /* Student Name */
        .roll-no { top: 69mm; left: 50mm;font-size: 48px } /* Roll Number */
        .class { top: 69mm; left: 130mm; font-size: 48px} /* Class */

        .payment-method { top: 81mm; left: 60mm; font-size: 48px} /* Payment Mode */
        .amount-in-words { top: 93mm; left: 60mm; width: 120mm; font-size: 14px; font-size: 48px} /* Amount in Words */

        .amount { top: 122mm; left: 27mm; font-size: 24px; font-size: 48px} /* Amount Paid */
        .status { top: 122mm; left: 160mm; font-size: 36px; border: 1px solid #000; padding: 3mm; text-align: center; } /* Status (Original/Duplicate) */

    </style>
</head>
<body>
    <div class="container">
        <div class="field receipt-no">{{ $receipt_no }}</div>
        <div class="field date">{{ $date }}</div>
        <div class="field name">{{ $name }}</div>
        <div class="field roll-no">{{ $roll_no }}</div>
        <div class="field class">{{ $class }}</div>
        <div class="field payment-method">{{ $payment_method }}</div>
        <div class="field amount-in-words">{{ $amount_in_words }}</div>
        <div class="field amount">{{ $amount }}</div>
        <div class="field status">{{ $status == 'PAID' ? 'ORIGINAL' : 'DUPLICATE' }}</div>
    </div>
</body>
</html>