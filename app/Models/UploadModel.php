<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadModel extends Model
{
    //
    protected $table = 't_uploads';

    protected $fillable = [
        'file_name', 'file_ext', 'file_url', 'file_size'];
}