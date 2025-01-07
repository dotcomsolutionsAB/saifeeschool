<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionModel extends Model
{
    //
    protected $table = 't_txns';

    protected $fillable = [
        'id',
        'st_id',                // Student ID (foreign key reference)
        'sch_id',               // School ID (foreign key reference)
        'txn_type_id',          // Transaction type ID
        'txn_date',             // Transaction date
        'txn_time',             // Transaction time
        'txn_mode',             // Transaction mode
        'txn_amount',           // Transaction amount
        'f_id',                 // Fee ID
        'f_normal',             // Flag for normal fee transactions
        'f_late',               // Flag for late fee transactions
        'txn_tagged_to_id',     // Tagged transaction ID
        'txn_reason',           // Reason for the transaction
        'date',                 // Transaction date as a `date` field
    ];

    
    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'st_id', 'id');
    }

    public function txnType()
    {
        return $this->belongsTo(TransactionTypeModel::class, 'txn_type_id', 'id');
    }

    // public function txnDetail()
    // {
    //     return $this->hasOne(TransactionDetailModel::class, 'txn_id', 'id');
    // }
}
