<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroupModel extends Model
{
    //
    protected $table = 't_class_groups';

    protected $fillable = [
        'ay_id', 'cg_name', 'cg_order'];
}
