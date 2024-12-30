<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .page-break {
            page-break-before: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Student List ({{ $academic_year }})</h1>
    @php $currentClass = null; @endphp
    @foreach ($students as $student)
        @if ($currentClass !== $student->Class)
            @if ($currentClass !== null)
                <div class="page-break"></div>
            @endif
            <h2>Class: {{ $student->Class }}</h2>
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
                    </tr>
                </thead>
                <tbody>
        @endif
        <tr>
            <td>{{ $student->SN }}</td>
            <td>{{ $student->Roll_No }}</td>
            <td>{{ $student->Name }}</td>
            <td>{{ $student->Gender }}</td>
            <td>{{ $student->DOB }}</td>
            <td>{{ $student->ITS }}</td>
            <td>{{ $student->Mobile }}</td>
        </tr>
        @php $currentClass = $student->Class; @endphp
        @if ($loop->last)
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
