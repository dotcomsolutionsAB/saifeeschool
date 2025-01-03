<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroupModel extends Model
{
    //
    protected $table = 't_class_groups';

    protected $fillable = [
        'id', 'ay_id', 'cg_name', 'cg_order'];

    public function fees()
    {
        return $this->hasMany(FeeModel::class, 'cg_id', 'id');
    }
}
