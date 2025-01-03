<!DOCTYPE html>
<html>
<head>
    <title>Pending Fees</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h3 {
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .student-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .fee-row {
            background-color: #fff;
        }
    </style>
</head>
<body>
    @foreach ($classes as $class)
        <h3>{{ $class['class_name'] }} (Generated on: {{ now()->format('d-m-Y h:i:s A') }})</h3>
        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Fee</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php $sn = 1; @endphp
                @foreach ($class['students'] as $student)
                    <tr class="student-row">
                        <td rowspan="{{ count($student['fees']) }}">{{ $sn++ }}</td>
                        <td rowspan="{{ count($student['fees']) }}">{{ $student['name'] }}</td>
                        <td rowspan="{{ count($student['fees']) }}">{{ $student['roll_no'] }}</td>
                        <td>{{ $student['fees'][0]['fee_name'] }}</td>
                        <td>{{ $student['fees'][0]['total'] }}</td>
                    </tr>
                    @foreach ($student['fees'] as $index => $fee)
                        @if ($index > 0)
                        <tr class="fee-row">
                            <td>{{ $fee['fee_name'] }}</td>
                            <td>{{ $fee['total'] }}</td>
                        </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
