<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonsModel extends Model
{
    //
    protected $table = 't_purchase_item_addons';

    protected $fillable = [
        'purchase_id', 'freight_value', 'freight_cgst', 'freight_sgst', 'freight_igst', 'roundoff'];

    public function purchase()
    {
        return $this->belongsTo(PurchaseModel::class, 'purchase_id');
    }
}
