<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarksModel extends Model
{
    //
    protected $table = 't_marks';

    protected $fillable = [
        'id', 'session', 'st_roll_no', 'subj_id', 'cg_id', 'term', 'unit', 'marks', 'prac', 'serialNo'];
}
