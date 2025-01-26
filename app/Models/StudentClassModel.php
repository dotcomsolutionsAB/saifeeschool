<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClassModel extends Model
{
    //
    protected $table = 't_student_classes';

    protected $fillable = [
        'ay_id', 'st_id', 'cg_id'];

    /**
     * Define a belongs-to relationship with AcademicYearModel.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYearModel::class, 'ay_id', 'id');
    }

    /**
     * Define a belongs-to relationship with ClassGroupModel.
     */
    public function classGroup()
    {
        return $this->belongsTo(ClassGroupModel::class, 'cg_id', 'id');
    }

    /**
     * Define a belongs-to relationship with StudentModel.
     */
    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'st_id', 'id');
    }
}
