<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentModel extends Model
{
    //
    protected $table = 't_students';

    protected $fillable = [
       'id', 'st_roll_no', 'st_first_name', 'st_last_name', 'st_gender', 'st_dob', 'st_blood_group', 'st_bohra', 'st_its_id', 'st_house', 'st_wallet', 'st_deposit', 'st_gmail_address', 'st_mobile', 'st_external', 'st_on_roll', 'st_year_of_admission', 'st_admitted', 'st_admitted_class', 'st_flag', 'photo_id', 'birth_certificate_id', 'aadhaar_id', 'attachment_id'
    ];

    /**
     * Define a one-to-one relationship with StudentDetails.
     */
    public function details()
    {
        return $this->hasOne(StudentDetailsModel::class, 'st_id', 'id');
    }

}
