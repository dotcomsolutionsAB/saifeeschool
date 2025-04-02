<!DOCTYPE html>
<html>
<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
        }
        th.rotate {
            height: 140px;
            white-space: nowrap;
        }
        th.rotate > div {
            transform: translate(0px, 60px) rotate(-90deg);
            width: 20px;
        }
    </style>
</head>
<body>
    <h2>Tabulation Report - {{ $data['class'] }} ({{ $data['year'] }})</h2>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                @foreach ($data['students'] as $student)
                    <th>{{ $student->st_roll_no }}<br>{{ $student->st_first_name }} {{ $student->st_last_name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['subjects'] as $subject)
                <tr>
                    <td>{{ $subject['subject_name'] }} ({{ $subject['category'] }})</td>
                    @foreach ($data['students'] as $student)
                        @php
                            $val = $data['marks'][$student->st_id][$subject['subject_id']][$subject['category'] === 'Practical' ? 'prac' : 'marks'] ?? '';
                        @endphp
                        <td>{{ $val }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>