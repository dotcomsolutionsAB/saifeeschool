<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tabulation Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th.rotate {
            height: 100px;
            white-space: nowrap;
        }
        th.rotate > div {
            transform: 
                translate(0px, 50px)
                rotate(-90deg);
            width: 30px;
        }
    </style>
</head>
<body>

<h2>Tabulation Report - {{ $class }} ({{ $year }})</h2>

<table>
    <thead>
        <tr>
            <th>SN</th>
            <th>Roll No</th>
            <th>Name</th>
            @foreach ($subjects as $subject)
                <th class="rotate"><div>{{ $subject->subject_name }} ({{ $subject->category }})</div></th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->st_roll_no }}</td>
                <td>{{ $student->st_first_name }} {{ $student->st_last_name }}</td>
                @foreach ($subjects as $subject)
                    @php
                        $value = $marks[$student->st_id][$subject->subject_id][$subject->category === 'Practical' ? 'prac' : 'marks'] ?? '';
                    @endphp
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>