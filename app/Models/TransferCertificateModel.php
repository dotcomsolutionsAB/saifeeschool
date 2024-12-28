<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferCertificateModel extends Model
{
    //
    protected $table = 't_transfer_certificate';

    protected $fillable = [
        'id', 'dated', 'serial_no', 'registration_no', 'st_id', 'st_roll_no', 'name', 'father_name', 'joining_class', 'joining_date', 'leaving_date', 'prev_school', 'character', 'class', 'stream', 'date_from', 'date_to', 'dob', 'dob_words', 'promotion', 'status'];
}
