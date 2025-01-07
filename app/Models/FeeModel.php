<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeModel extends Model
{
    //
    protected $table = 't_fees';

    protected $fillable = [
        'id',
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

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'st_id', 'id');
    }

     /**
     * Relationship to StudentClassModel through StudentModel
     */
    public function studentClass()
    {
        return $this->hasOneThrough(
            StudentClassModel::class,
            StudentModel::class,
            'id',       // Foreign key on t_student
            'st_id',    // Foreign key on t_student_class
            'st_id',    // Local key on t_fees
            'id'        // Local key on t_student
        );
    }

    /**
     * Relationship to ClassGroupModel through StudentClassModel
     */
    public function classGroup()
    {
        return $this->hasOneThrough(
            ClassGroupModel::class,
            StudentClassModel::class,
            'st_id',    // Foreign key on t_student_class
            'cg_id',    // Foreign key on t_class_group
            'st_id',    // Local key on t_fees
            'id'        // Local key on t_student_class
        );
    }
}
