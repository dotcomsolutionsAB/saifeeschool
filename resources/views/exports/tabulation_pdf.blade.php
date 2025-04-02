<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tabulation Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: auto;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th.subject-col {
            text-align: left;
        }

        .header {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            padding: 10px 0;
        }

        .sub-header {
            text-align: center;
            font-size: 14px;
            padding-bottom: 10px;
        }

        .rotate {
            transform: rotate(-90deg);
            white-space: nowrap;
        }
    </style>
</head>
<body>

    <div class="header">Tabulation Report</div>
    <div class="sub-header">{{ $data['class'] }} - {{ $data['year'] }}</div>

    <table>
        <thead>
            <tr>
                <th>Subject (Type)</th>
                @foreach ($data['students'] as $student)
                    <th>
                        {{ $student->st_roll_no ?? '' }}<br>
                        {{ $student->st_first_name }} {{ $student->st_last_name }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['subjects'] as $subject)
                @php
                    $subjectLabel = $subject['subject_name'] . ' (' . $subject['category'] . ')';
                    $subjId = $subject['subject_id'];
                    $cat = $subject['category'] === 'Practical' ? 'prac' : 'marks';
                @endphp
                <tr>
                    <td class="subject-col">{{ $subjectLabel }}</td>
                    @foreach ($data['students'] as $student)
                        <td>{{ $data['marks'][$student->st_id][$subjId][$cat] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>