<?php

// app/Models/CreditNote.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    // Table associated with the model
    protected $table = 't_credit_notes';

    // The attributes that are mass assignable
    protected $fillable = [
        'credit_no',
        'date',
        'amount',
        'collected_from',
        'cheque_no',
        'description',
        'log_user',
        'log_date'
    ];

    // Cast the 'date' and 'log_date' attributes to Carbon instances (date handling)
    protected $dates = ['date', 'log_date'];

    // Adding timestamps explicitly if needed (Laravel will add created_at and updated_at automatically)
    public $timestamps = false; // If the table doesn't have created_at and updated_at
}