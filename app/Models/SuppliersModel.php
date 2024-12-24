<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliersModel extends Model
{
    //
    protected $table = 't_suppliers';

    protected $fillable = [
       'id', 'company', 'name', 'address', 'state', 'country', 'mobile', 'email', 'documents', 'bank_details', 'notes', 'gstin', 'gstin_type', 'notification', 'log_user', 'log_date'
    ];
}
