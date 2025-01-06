<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PGResponseModel extends Model
{
    //
    protected $table = 't_pg_responses';

    protected $fillable = [
        'id', 'response_code', 'unique_ref_number', 'transaction_date', 'transaction_time', 'total_amount', 'interchange_value', 'tdr', 'payment_mode', 'submerchant_id', 'reference_no', 'icid', 'rs', 'tps', 'mandatory_fields', 'optional_fields', 'rsv'];

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'submerchant_id', 'id');
    }
}
