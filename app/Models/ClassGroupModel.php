<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroupModel extends Model
{
    //
    protected $table = 't_academic_years';

    protected $fillable = [
        'ay_id', 'cg_name', 'cg_order'];
}
