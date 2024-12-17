<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClassModel extends Model
{
    //
    protected $table = 't_student_classes';

    protected $fillable = [
        'ay_id', 'st_id', 'cg_id'];
}
