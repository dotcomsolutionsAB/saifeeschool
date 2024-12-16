<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetailsModel extends Model
{
    //
    protected $table = 't_student_details';

    protected $fillable = [
        'st_id', 'aadhaar_no', 'residential_address1', 'residential_address2', 'residential_address3', 'city', 'state', 'country', 'pincode', 'class_group', 'f_name', 'f_email', 'f_contact', 'f_occupation', 'f_business_name', 'f_business_nature', 'f_business_address1', 'f_business_address2', 'f_business_city', 'f_business_state', 'f_business_country', 'f_business_pincode', 'f_employer_name',
        'f_designation', 'f_work_address1', 'f_work_address2', 'f_work_city', 'f_work_state', 'f_work_country', 'f_work_pincode', 'm_name', 'm_contact', 'm_occupation', 'm_business_name', 'm_business_nature', 'm_business_address1', 'm_business_address2', 'm_business_city', 'm_business_state', 'm_business_country', 'm_business_pincode',
        'm_employer_name', 'm_designation', 'm_work_address1', 'm_work_address2', 'm_work_city', 'm_work_state', 'm_work_country', 'm_work_pincode'
    ];

     /**
     * Define a one-to-one inverse relationship with Student.
     */
    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'st_id', 'id');
    }
}
