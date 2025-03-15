<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $transactions;

    public function __construct(Collection $transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'SN',
            'Name',
            'Roll No',
            'Fee',
            'Fee Amount',
            'Concession',
            'Due Date',
            'Late Fee Applicable',
            'Total Amount',
            'Status',
        ];
    }

    public function map($transaction): array
    {
        static $sn = 0;
        $sn++;

        return [
            $sn,
            $transaction->student_name,
            $transaction->st_roll_no,
            $transaction->fee_name,
            $transaction->base_amount,
            $transaction->concession ?? 0,
            date('d-m-Y', strtotime($transaction->due_date)),
            $transaction->late_fee,
            $transaction->total_amount,
            $transaction->payment_status === '1' ? 'Paid' : 'Pending',
        ];
    }
}