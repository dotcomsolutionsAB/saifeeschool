<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeModel extends Model
{
    //
    protected $table = 't_fees';

    protected $fillable = [
        'st_id', 
        'st_roll_no', 
        'fpp_id', 
        'cg_id', 
        'ay_id', 
        'fpp_name', 
        'fpp_due_date', 
        'fpp_month_no', 
        'fpp_year_no', 
        'fpp_amount', 
        'f_concession', 
        'fpp_late_fee', 
        'f_late_fee_applicable', 
        'f_late_fee_paid', 
        'f_total_paid', 
        'f_paid', 
        'f_paid_date', 
        'f_active', 
        'fp_recurring', 
        'fp_main_monthly_fee', 
        'fp_main_admission_fee'
        ];
}
