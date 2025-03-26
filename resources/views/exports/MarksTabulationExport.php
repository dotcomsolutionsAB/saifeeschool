<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .sub-title {
            text-align: center;
            font-style: italic;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
        .rotate {
            transform: rotate(-90deg);
            writing-mode: vertical-lr;
            white-space: nowrap;
            width: 30px;
        }
    </style>
</head>
<body>
    <div class="title">SAIFEE GOLDEN JUBILEE ENGLISH PUBLIC SCHOOL</div>
    <div class="sub-title">TABULATION SHEET</div>

    @foreach($data as $className => $rows)
        <div><strong>Class:</strong> {{ $className }}</div>
        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    @foreach($headers as $header)
                        <th class="rotate">{{ $header }}</th>
                    @endforeach
                    <th>Total</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['SN'] }}</td>
                        <td>{{ $row['Roll No'] }}</td>
                        <td>{{ $row['Name'] }}</td>
                        @foreach($headers as $header)
                            <td>{{ $row[$header] ?? '' }}</td>
                        @endforeach
                        <td>{{ $row['Total Marks'] ?? '' }}</td>
                        <td>{{ $row['Attendance'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
    @endforeach
</body>
</html>
