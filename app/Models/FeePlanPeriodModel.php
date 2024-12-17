<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePlanPeriodModel extends Model
{
    //
    protected $table = 't_fee_plan_periods';

    protected $fillable = [
        'fp_id', 'ay_id', 'fpp_name', 'fpp_amount', 'fpp_late_fee', 'fpp_due_date', 'fpp_month_no', 'fpp_year_no', 'fpp_order_no'];
}
