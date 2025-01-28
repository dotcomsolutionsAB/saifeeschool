<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransferExport implements FromCollection, WithHeadings
{
    protected $data;

    /**
     * Constructor to initialize data
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Provide the data for export
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Provide the headings for the export
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'SN', 'Date', 'Roll No', 'Name', 'Father Name', 'Joining Class',
            'Joining Date', 'Leaving Date', 'Previous School', 'Character',
            'Class', 'Stream', 'Date From', 'Date To', 'DOB', 'Promotion',
        ];
    }
}