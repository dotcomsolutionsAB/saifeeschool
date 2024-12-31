<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherApplicationModel extends Model
{
    //
    protected $table = 't_teacher_application';

    protected $fillable = [
       'id', 'name', 'gender', 'dob', 'contact_number', 'email', 'blood_group', 'designation', 'qualification', 'experience_years', 'teacher_type', 'joining_date', 'leaving_date', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode', 'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_number', 'resume_path', 'photo_path', 'id_proof_path', 'qualification_docs_path', 'status', 'remarks'
    ];
}
