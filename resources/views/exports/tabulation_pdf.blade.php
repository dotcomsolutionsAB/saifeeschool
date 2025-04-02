<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tabulation Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .rotate {
            writing-mode: vertical-lr;
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <h3>{{ $class }} - {{ $year }} Tabulation</h3>

    <table>
        <thead>
            <tr>
                <th>SN</th>
                <th>Roll No</th>
                <th>Name</th>
                @foreach ($subjects as $subject)
                    <th>
                        <div class="rotate">{{ $subject['subject_name'] }} ({{ $subject['category'] }})</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->roll_no }}</td>
                    <td>{{ $student->name }}</td>
                    @foreach ($subjects as $subject)
                        <td>
                            {{ $marks[$student->st_id][$subject['subject_id']][$subject['category'] === 'Practical' ? 'prac' : 'marks'] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>