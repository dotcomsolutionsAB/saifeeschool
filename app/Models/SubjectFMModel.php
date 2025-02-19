<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectFMModel extends Model
{
    protected $table = 't_subjectFM';

    protected $fillable = [
        'subj_id', 'subj_name', 'subj_init', 'cg_id', 'term',
        'type', 'prac', 'marks'
    ];

    // Relationship with Subjects
    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subj_id', 'id');
    }

    // Relationship with Class Groups
    public function classGroup()
    {
        return $this->belongsTo(ClassGroupModel::class, 'cg_id', 'id');
    }

    // Relationship with Terms
   
}