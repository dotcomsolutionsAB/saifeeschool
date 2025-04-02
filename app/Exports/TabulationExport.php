<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TabulationExport implements FromArray, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        // Convert stdClass to array recursively (if needed)
        $this->data = json_decode(json_encode($data), true);
    }

    public function array(): array
    {
        $subjects = $this->data['subjects'];
        $students = $this->data['students'];
        $marksData = $this->data['marks'];

        $rows = [];

        foreach ($students as $index => $student) {
            $row = [
                'SN'      => $index + 1,
                'Roll No' => $student['roll_no'] ?? '',
                'Name'    => $student['name'] ?? '',
            ];

            foreach ($subjects as $subject) {
                $subjId = $subject['subject_id'];
                $cat = ($subject['category'] ?? '') === 'Practical' ? 'prac' : 'marks';
                $value = $marksData[$student['st_id']][$subjId][$cat] ?? '';
                $colName = ($subject['subject_name'] ?? 'Subject') . ' (' . ($subject['category'] ?? '-') . ')';
                $row[$colName] = $value;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $headers = ['SN', 'Roll No', 'Name'];

        foreach ($this->data['subjects'] as $subject) {
            $headers[] = ($subject['subject_name'] ?? 'Subject') . ' (' . ($subject['category'] ?? '-') . ')';
        }

        return $headers;
    }

    public function title(): string
    {
        return ($this->data['class'] ?? 'Class') . ' - ' . ($this->data['year'] ?? 'Year');
    }
}