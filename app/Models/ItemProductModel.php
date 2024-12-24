<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemProductModel extends Model
{
    //
    protected $table = 't_purchase_item_products';

    protected $fillable = [
        'purchase_id', 'product', 'description', 'quantity', 'unit', 'price', 'discount', 'hsn', 'tax', 'cgst', 'sgst', 'igst'];

    public function purchase()
    {
        return $this->belongsTo(PurchaseModel::class, 'purchase_id');
    }
}
