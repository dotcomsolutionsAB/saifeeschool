<?php

namespace App\Models;

// app/Models/Bank.php



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    // Table associated with the model
    protected $table = 't_banks';

    // The attributes that are mass assignable
    protected $fillable = [
        'type',       // Deposit/Withdrawal
        'amount',
        'comments',
        'date',
        'log_user'
    ];

    // The attributes that should be hidden for arrays
    protected $hidden = [];

    // Cast the 'date' attribute to a Carbon instance (date handling)
    protected $dates = ['date'];

    // Adding timestamps explicitly if needed (Laravel will add created_at and updated_at automatically)
    public $timestamps = true;
}