<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Certificate</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('{{ storage_path("app/public/pdf/tc.jpg") }}');
            background-size: cover;
            background-position: top left;
            width: 210mm;
            height: 297mm;
            position: relative;
            font-size: 38px; /* 🔹 Increase font size */
        }
        .container {
            position: absolute;
            width: 100%;
            height: 100%;
            padding: 20mm;
        }
        .field {
            position: absolute;
            font-size: 46px; /* 🔹 Increased for clarity */
            font-weight: bold;
            text-align: center;
        }
        /* Positioning Each Field Based on MPDF setXY */
        .serial-no { top: 82mm; left: 35mm; width: 20mm; }
        .reg-no { top: 82mm; left: 172mm; width: 20mm; }
        .name { top: 98mm; left: 50mm; width: 140mm; font-size: 38px; font-style: italic; } /* 🔹 Increased */
        .father-name { top: 108mm; left: 25mm; width: 165mm; font-size: 38px; font-style: italic; } /* 🔹 Increased */
        .joining-date { top: 119mm; left: 72mm; width: 60mm; font-size: 36px; }
        .joining-class { top: 119mm; left: 120mm; width: 60mm; font-size: 36px; }
        .leaving-date { top: 129mm; left: 160mm; width: 30mm; font-size: 36px; }
        .prev-school { top: 129mm; left: 10mm; width: 120mm; font-size: 24px; font-style: italic; }
        .character { top: 140mm; left: 20mm; width: 30mm; font-size: 36px; }
        .class { top: 151mm; left: 80mm; width: 30mm; font-size: 36px; }
        .stream { top: 151mm; left: 150mm; width: 30mm; font-size: 36px; }
        .date-from { top: 161mm; left: 65mm; width: 30mm; font-size: 36px; }
        .date-to { top: 161mm; left: 125mm; width: 30mm; font-size: 36px; }
        .dob { top: 192mm; left: 150mm; width: 30mm; font-size: 36px; }
        .dob-words { top: 203mm; left: 25mm; width: 165mm; font-size: 24px; font-style: italic; } /* 🔹 Increased */
        .promotion { top: 214mm; left: 50mm; width: 50mm; font-size: 36px; font-style: italic; text-transform: uppercase; }
        .dated { top: 232mm; left: 23mm; width: 50mm; font-size: 24px; }
        .status { top: 380mm; left: 85mm; width: 40mm; font-size: 36px; border: 1px solid #000; padding: 3mm; }
    </style>
</head>
<body>
    <div class="container">
        <div class="field serial-no">{{ $serial_no }}</div>
        <div class="field reg-no">{{ $registration_no }}</div>
        <div class="field name">{{ $name }}</div>
        <div class="field father-name">{{ $father_name }}</div>
        <div class="field joining-date">{{ $joining_date }}</div>
        <div class="field joining-class">{{ $joining_class }}</div>
        <div class="field leaving-date">{{ $leaving_date }}</div>
        <div class="field prev-school">{{ $prev_school }}</div>
        <div class="field character">{{ $character }}</div>
        <div class="field class">{{ $class }}</div>
        <div class="field stream">{{ $stream }}</div>
        <div class="field date-from">{{ $date_from }}</div>
        <div class="field date-to">{{ $date_to }}</div>
        <div class="field dob">{{ date('d-m-Y', strtotime($dob)) }}</div>
        <div class="field dob-words">{{ $dob_words }}</div>
        <div class="field promotion">{{ strtoupper($promotion) }}</div>
        <div class="field dated">{{ $dated }}</div>
        <div class="field status">{{ $status == '0' ? 'ORIGINAL' : 'DUPLICATE' }}</div>
    </div>
</body>
</html>