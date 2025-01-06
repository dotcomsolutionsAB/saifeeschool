<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailyTransactionExport implements FromCollection, WithHeadings
{
    private $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return collect($this->transactions);
    }

    public function headings(): array
    {
        return ['SN', 'Name', 'Roll No', 'Date', 'Unique Ref No', 'Total Amount', 'Status', 'Mode'];
    }
}
