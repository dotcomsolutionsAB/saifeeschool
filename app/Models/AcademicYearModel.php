<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYearModel extends Model
{
    //
    protected $table = 't_academic_years';

    protected $fillable = [
        'id', 'sch_id', 'ay_name', 'ay_start_year', 'ay_start_month', 'ay_end_year', 'ay_end_month', 'ay_current'];

        
    /**
     * Define a one-to-many relationship with StudentClassModel.
     */
    public function studentClasses()
    {
        return $this->hasMany(StudentClassModel::class, 'ay_id', 'id');
    }

    /**
     * Define a one-to-many relationship with ClassGroupModel.
     */
    public function classGroups()
    {
        return $this->hasMany(ClassGroupModel::class, 'ay_id', 'id');
    }
    public function terms()
{
    return $this->hasMany(TTermModel::class, 'ay_id', 'id');
}
}
