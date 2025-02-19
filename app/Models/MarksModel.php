<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarksModel extends Model
{
    //
    protected $table = 't_marks';

    protected $fillable = [
        'id', 'marks_id','ay_id', 'st_id','st_roll_no', 'subj_id', 'cg_id', 'term', 'marks', 'prac', 'serialNo'];
}
