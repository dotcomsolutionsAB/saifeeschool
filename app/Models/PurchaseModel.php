<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseModel extends Model
{
    //
    protected $table = 't_purchases';

    protected $fillable = [
       'id', 'supplier', 'purchase_invoice_no', 'purchase_invoice_date', 'series', 'currency', 'total', 'paid', 'cgst', 'sgst', 'igst', 'status', 'log_user', 'log_date'
    ];

    // Relationship with products table
    public function products()
    {
        return $this->hasMany(ItemProductModel::class, 'purchase_id');
    }

    // Relationship with addons table
    public function addons()
    {
        return $this->hasMany(AddonsModel::class, 'purchase_id');
    }
}
