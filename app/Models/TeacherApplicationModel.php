<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherApplicationModel extends Model
{
    //
    protected $table = 't_teacher_application';

    protected $fillable = [
       'id', 'name', 'gender', 'dob', 'contact_number', 'email', 'apply_for', 'qualification', 'experience_years', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode', 'resume_path', 'photo_path', 'id_proof_path', 'qualification_docs_path', 'status', 'remarks'
    ];

    // Relationship for resume
    public function resume()
    {
        return $this->belongsTo(UploadModel::class, 'resume_path');
    }

    // Relationship for photo
    public function photo()
    {
        return $this->belongsTo(UploadModel::class, 'photo_path');
    }

    // Relationship for ID proof
    public function idProof()
    {
        return $this->belongsTo(UploadModel::class, 'id_proof_path');
    }

    // Relationship for qualification documents
    public function qualificationDocs()
    {
        return $this->belongsTo(UploadModel::class, 'qualification_docs_path');
    }
}
