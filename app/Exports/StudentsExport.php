<?php

namespace App\Exports;

// use App\Models\StudentModel;
// use Maatwebsite\Excel\Concerns\FromCollection;

// class StudentsExport implements FromCollection
// {
//     /**
//     * @return \Illuminate\Support\Collection
//     */
//     // public function collection()
//     // {
//     //     return StudentModel::all();
//     // }

//     protected $students;

//     public function __construct($students)
//     {
//         $this->students = $students;
//     }

//     public function collection()
//     {
//         return $this->students;
//     }

//     public function headings(): array
//     {
//         return ['SN', 'Roll No', 'Name', 'Class', 'Gender', 'DOB', 'ITS', 'Mobile'];
//     }
// }

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    /**
     * Return the collection of students.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->students); // Ensure the data is wrapped in a collection
    }

    /**
     * Define the headings for the export.
     *
     * @return array
     */
    public function headings(): array
    {
        return ['SN', 'Roll No', 'Name', 'Class', 'Gender', 'DOB', 'ITS', 'Mobile', 'Bohra', 'Academic Year'];
    }
}
