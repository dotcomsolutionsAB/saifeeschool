<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYearModel extends Model
{
    //
    protected $table = 't_academic_years';

    protected $fillable = [
        'sch_id', 'ay_name', 'ay_start_year', 'ay_start_month', 'ay_end_year', 'ay_end_month', 'ay_current'];

        
    public function studentClasses()
    {
        return $this->hasMany(StudentClassModel::class, 'ay_id', 'id');
    }
}
