<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterCertificateModel extends Model
{
    //
    protected $table = 't_character_certificate';

    protected $fillable = [
        'id', 'dated', 'serial_no', 'registration_no', 'st_id', 'st_roll_no', 'name', 'joining_date', 'leaving_date', 'stream', 'date_from', 'dob', 'dob_words'];
}
