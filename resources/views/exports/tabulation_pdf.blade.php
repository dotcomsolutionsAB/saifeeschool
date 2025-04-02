<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tabulation Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            word-wrap: break-word;
        }
        .rotate {
            transform: rotate(-90deg);
            white-space: nowrap;
            font-weight: bold;
        }
        .rotate-header {
            height: 200px;
            vertical-align: bottom;
        }
        .vertical-text {
            writing-mode: vertical-lr;
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <h2>Tabulation Report - {{ $data['class'] ?? 'Class' }} ({{ $data['year'] ?? 'Year' }})</h2>
    <table>
        <thead>
            <tr>
                <th rowspan="2">SN</th>
                <th rowspan="2">Roll No</th>
                <th rowspan="2">Name</th>
                @foreach ($data['subjects'] as $subject)
                    <th class="rotate-header">
                        <div class="vertical-text">
                            {{ $subject['subject_name'] ?? '' }}<br>
                            <small>({{ $subject['category'] ?? '' }})</small>
                        </div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['students'] as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student['st_roll_no'] ?? '' }}</td>
                    <td>{{ $student['st_first_name'] ?? '' }} {{ $student['st_last_name'] ?? '' }}</td>
                    @foreach ($data['subjects'] as $subject)
                        @php
                            $subjId = $subject['subject_id'];
                            $cat = $subject['category'] === 'Practical' ? 'prac' : 'marks';
                            $mark = $data['marks'][$student['st_id']][$subjId][$cat] ?? '';
                        @endphp
                        <td>{{ $mark }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
