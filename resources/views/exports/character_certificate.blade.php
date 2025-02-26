<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Character Certificate</title>
    <style>
        @page { size: A4; margin: 0; }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            font-size: 22px; /* 🔹 Slightly increased base font size */
            background-image: url('{{ storage_path("app/public/pdf/cc.jpg") }}'); 
            background-size: cover; /* Ensures full background fit */
            background-position: center; 
            width: 210mm; 
            height: 297mm; 
            position: relative; 
        }
        .container { position: absolute; width: 100%; height: 100%; padding: 20mm; }
        .field { 
            position: absolute; 
            font-size: 56px; /* 🔹 Increased font size for 300 DPI */
            font-weight: bold; 
            text-align: center; 
        }

        /* Positioning Fields */
        .serial-no { top: 82mm; left: 30mm; width: 25mm; }
        .reg-no { top: 82mm; left: 172mm; width: 25mm; }
        .name { top: 100mm; left: 50mm; width: 150mm; font-style: italic; }
        .years { top: 109mm; left: 170mm; width: 40mm; font-style: italic; text-transform: uppercase; }
        .joining-date { top: 116mm; left: 35mm; width: 35mm; font-style: italic; }
        .leaving-date { top: 116mm; left: 85mm; width: 35mm; font-style: italic; }
        .stream { top: 125mm; left: 50mm; width: 35mm; font-style: italic; }
        .date-from { top: 125mm; left: 110mm; width: 60mm; font-style: italic; }
        .dob { top: 145mm; left: 10mm; width: 200mm; font-style: italic; }

    </style>
</head>
<body>
    <div class="container">
        @foreach ($data as $record)
            <div class="field serial-no">{{ $record['serial_no'] }}</div>
            <div class="field reg-no">{{ $record['registration_no'] }}</div>
            <div class="field name">{{ $record['name'] }}</div>
            <div class="field years">{{ strtoupper($record['years_in_words']) }}</div>
            <div class="field joining-date">{{ $record['joining_date'] }}</div>
            <div class="field leaving-date">{{ $record['leaving_date'] }}</div>
            <div class="field stream">{{ $record['stream'] }}</div>
            <div class="field date-from">{{ $record['date_from'] }}</div>
            <div class="field dob">{{ $record['dob_words'] }}</div>
        @endforeach
    </div>
</body>
</html>