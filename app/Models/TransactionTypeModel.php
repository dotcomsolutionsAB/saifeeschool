<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionTypeModel extends Model
{
    //
    protected $table = 't_txn_types';

    protected $fillable = [
        'id',
        'txn_type_from',
        'txn_type_to',
        'txn_type_name',
        'txn_type_description',
    ];
}
