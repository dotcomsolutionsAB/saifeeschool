<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroupModel extends Model
{
    //
    protected $table = 't_class_groups';

    protected $fillable = [
        'id', 'ay_id', 'cg_name', 'cg_order','teacher_id'];

    /**
     * Define a belongs-to relationship with AcademicYearModel.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYearModel::class, 'ay_id', 'id');
    }

    /**
     * Define a one-to-many relationship with StudentClassModel.
     */
    public function studentClasses()
    {
        return $this->hasMany(StudentClassModel::class, 'cg_id', 'id');
    }

    /**
     * Define a one-to-many relationship with FeeModel.
     */
    public function fees()
    {
        return $this->hasMany(FeeModel::class, 'cg_id', 'id');
    }
}
