<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TTermModel extends Model
{
    protected $table = 't_terms';

    protected $fillable = [
        'ay_id', 'cg_id', 'term', 'term_name'
    ];

    /**
     * Relationship: Each Term belongs to one Academic Year.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYearModel::class, 'ay_id', 'id');
    }

    /**
     * Relationship: Each Term belongs to one Class Group.
     */
    public function classGroup()
    {
        return $this->belongsTo(ClassGroupModel::class, 'cg_id', 'id');
    }
}