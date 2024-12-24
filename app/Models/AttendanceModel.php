<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceModel extends Model
{
    //
    protected $table = 't_attendances';

    protected $fillable = [
        'id', 'session', 'st_roll_no', 'cg_id', 'term', 'unit', 'attendance', 'total_days'];
}
