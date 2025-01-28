<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Transfer Certificate</h1>
    <h2>Certificate Details</h2>
    <table>
        <tr>
            <th>Serial Number</th>
            <td>{{ $data['serial_no'] }}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{{ $data['date'] }}</td>
        </tr>
        <tr>
            <th>Roll Number</th>
            <td>{{ $data['roll_no'] }}</td>
        </tr>
        <tr>
            <th>Student Name</th>
            <td>{{ $data['name'] }}</td>
        </tr>
        <tr>
            <th>Father's Name</th>
            <td>{{ $data['father_name'] }}</td>
        </tr>
        <tr>
            <th>Joining Class</th>
            <td>{{ $data['joining_class'] }}</td>
        </tr>
        <tr>
            <th>Joining Date</th>
            <td>{{ $data['joining_date'] }}</td>
        </tr>
        <tr>
            <th>Leaving Date</th>
            <td>{{ $data['leaving_date'] }}</td>
        </tr>
        <tr>
            <th>Previous School</th>
            <td>{{ $data['prev_school'] }}</td>
        </tr>
        <tr>
            <th>Character</th>
            <td>{{ $data['character'] }}</td>
        </tr>
        <tr>
            <th>Class</th>
            <td>{{ $data['class'] }}</td>
        </tr>
        <tr>
            <th>Stream</th>
            <td>{{ $data['stream'] }}</td>
        </tr>
        <tr>
            <th>Date From</th>
            <td>{{ $data['date_from'] }}</td>
        </tr>
        <tr>
            <th>Date To</th>
            <td>{{ $data['date_to'] }}</td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td>{{ $data['dob'] }}</td>
        </tr>
        <tr>
            <th>Promotion</th>
            <td>{{ $data['promotion'] }}</td>
        </tr>
    </table>
</body>
</html>