<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFeesModel extends Model
{
    //
    protected $table = 't_payment_fees';

    protected $fillable = [
        'st_id', 'order_id', 'against_fees', 'order_status'];
}
