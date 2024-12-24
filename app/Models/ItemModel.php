<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemModel extends Model
{
    //
    protected $table = 't_items';

    protected $fillable = [
        'name', 'description', 'category', 'sub_category', 'unit', 'price', 'discount', 'tax', 'hsn', 'log_user', 'log_date'];
}
