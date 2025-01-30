<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 30px;
        }
        .certificate-container {
            border: 5px solid #000;
            padding: 30px;
            text-align: center;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .certificate-title {
            font-size: 24px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 20px;
        }
        .details {
            text-align: left;
            margin-top: 20px;
            font-size: 16px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }
        .date {
            text-align: right;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="certificate-container">
    <div class="header">
        <strong>SAIFEE GOLDEN JUBILEE SCHOOL</strong> <br>
        <span>Affiliated to CBSE | Recognized by Govt. of India</span> <br>
        <span>Address: [Your School Address Here]</span> <br>
    </div>

    <div class="certificate-title">CHARACTER CERTIFICATE</div>

    <div class="details">
        <p>Serial No: <strong>{{ $data['serial_no'] }}</strong></p>
        <p>Date: <strong>{{ $data['date'] }}</strong></p>
        <p>This is to certify that <strong>{{ $data['name'] }}</strong>, Roll No. <strong>{{ $data['roll_no'] }}</strong>,
            was a student of our institution. He/She was admitted to our school on <strong>{{ $data['joining_date'] }}</strong>
            and left on <strong>{{ $data['leaving_date'] }}</strong>.</p>

        <p>The student was in the <strong>{{ $data['stream'] }}</strong> stream and attended classes from
            <strong>{{ $data['date_from'] }}</strong>.</p>

        <p>Date of Birth (DOB): <strong>{{ $data['dob'] }}</strong> ({{ $data['dob_words'] }})</p>

        <p>To the best of my knowledge and belief, his/her conduct and character were found to be <strong>EXCELLENT</strong> during his/her stay in the institution.</p>
    </div>

    <div class="signature">
        <p>Principal / Headmaster</p>
    </div>

    <div class="date">
        <p>Seal & Signature</p>
    </div>
</div>

</body>
</html>