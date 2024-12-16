<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherModel extends Model
{
    //
    protected $table = 't_teachers';

    protected $fillable = [
        'name', 'address', 'email', 'gender', 'dob', 'blood_group', 'is_class_teacher', 'degree', 'qualification'
    ];
}
