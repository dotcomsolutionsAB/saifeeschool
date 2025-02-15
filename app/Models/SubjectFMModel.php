<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectFMModel extends Model
{
    use HasFactory;

    protected $table = 't_subjectFM';

    protected $fillable = [
        'ay_id',
        'cg_id',
        'subject_id',
        'full_marks',
        'pass_marks',
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYearModel::class, 'ay_id');
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroupModel::class, 'cg_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_id');
    }
}