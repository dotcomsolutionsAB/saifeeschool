<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectModel extends Model
{
    //
    protected $table = 't_subjects';

    protected $fillable = [
       'id', 'subj_name', 'cg_group', 'type', 'marks', 'prac', 'serial', 'category'
    ];
}
