<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 0 auto;
            padding: 20px;
        }
        h3 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        p {
            text-align: center;
            font-size: 14px;
            margin-top: -10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Student Data</h3>
        @if(isset($academicYear))
            <p>Academic Year: {{ $academicYear }}</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>DOB</th>
                    <th>ITS</th>
                    <th>Mobile</th>
                    <th>Bohra</th>
                    <th>Academic Year</th>
                    <th>Class Group ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $student)
                    <tr>
                        <td>{{ $student['SN'] }}</td>
                        <td>{{ $student['Roll No'] }}</td>
                        <td>{{ $student['Name'] }}</td>
                        <td>{{ $student['Class'] }}</td>
                        <td>{{ $student['Gender'] }}</td>
                        <td>{{ $student['DOB'] }}</td>
                        <td>{{ $student['ITS'] }}</td>
                        <td>{{ $student['Mobile'] }}</td>
                        <td>{{ $student['Bohra'] }}</td>
                        <td>{{ $student['Academic Year'] }}</td>
                        <td>{{ $student['Class Group ID'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center;">No student data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
