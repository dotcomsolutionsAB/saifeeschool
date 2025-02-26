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
            margin-bottom: 10px;
            font-size: 18px;
        }
        .class-header {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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

        @php
            $firstClass = true;
        @endphp

        @foreach($data as $class => $students)
            @if(!$firstClass)
                <div class="page-break"></div>
            @endif
            @php $firstClass = false; @endphp
            
            <div class="class-header">Class: {{ $class }}</div>

            <table>
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>ITS</th>
                        <th>Mobile</th>
                        <th>Bohra</th>
                        <th>House</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student['Roll No'] }}</td>
                            <td>{{ $student['Name'] }}</td>
                            <td>{{ $student['Gender'] }}</td>
                            <td>{{ $student['DOB'] }}</td>
                            <td>{{ $student['ITS'] }}</td>
                            <td>{{ $student['Mobile'] }}</td>
                            <td>{{ $student['Bohra'] }}</td>
                            <td>{{ $student['st_house'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center;">No student data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    </div>
</body>
</html>