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
            page-break-before: always;  
        }
        h3:first-of-type {
            page-break-before: auto;
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
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        
        .class-group {
            page-break-before: always; /* Ensure each class starts on a new page */
        }
        .class-group:first-of-type {
            page-break-before: auto; /* Prevent page break before the first class */
        }
    </style>
</head>
<body>
    @foreach ($classes as $class)
    <div class="class-group">
        <h3>{{ $class['class_name'] }} (Generated on: {{ now()->format('d-m-Y h:i:s A') }})</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">SN</th>
                    <th style="width: 35%;">Student</th>
                    <th style="width: 15%;">Roll No</th>
                    <th style="width: 40%;">Fee</th>
                    <th style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $sn = 1; @endphp
                @foreach ($class['students'] as $student)
                    <tr class="student-row">
                        <td rowspan="{{ count($student['fees']) + 1 }}">{{ $sn++ }}</td>
                        <td rowspan="{{ count($student['fees']) + 1 }}">{{ $student['name'] }}</td>
                        <td rowspan="{{ count($student['fees']) + 1 }}">{{ $student['roll_no'] }}</td>
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
                    <!-- Total Row Inside the Table -->
                    <tr class="total-row">
                        <td>Total:</td>
                        <td>{{ $student['student_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</body>
</html>
