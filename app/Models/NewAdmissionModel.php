<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewAdmissionModel extends Model
{
    //
    protected $table = 't_new_admission';

    protected $fillable = [
       'id', 'application_no', 'ay_id', 'class', 'date', 'first_name', 'last_name', 'gender', 'date_of_birth', 'last_school', 'last_school_address', 'aadhaar_no', 'father_first_name', 'father_last_name', 'father_name', 'father_occupation', 'father_employer', 'father_designation', 'father_business', 'father_business_nature', 'father_monthly_income', 'father_mobile', 'father_email', 'father_work_business_address', 'mother_first_name', 'mother_last_name', 'mother_name', 'mother_occupation', 'mother_employer', 'mother_designation', 'mother_business', 'mother_business_nature', 'mother_monthly_income', 'mother_mobile', 'mother_email', 'mother_work_business_address', 'siblings', 'address_1', 'address_2', 'city', 'state', 'country', 'pincode', 'other_info', 'ad_paid', 'transaction_id', 'transaction_date', 'interview_date', 'interview_status', 'added_to_school', 'comments', 'printed'
    ];
}
