<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePlanModel extends Model
{
    //
    protected $table = 't_fee_plans';

    protected $fillable = [
        'id', 'ay_id', 'fp_name', 'fp_recurring', 'fp_main_monthly_fee', 'fp_main_admission_fee', 'cg_id'];
}
