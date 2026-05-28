<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase_Notes;
use App\Models\Insumo;

class Details_Purchases extends Model
{
        protected $table = 'details_purchases';

    protected $fillable = [
        'insumos_id',
        'purchase_notes_id',
        'amount',
        'price_purchase',
    ];

    public function purchase_notes()
    {
        return $this->belongsTo(Purchase_Notes::class, 'purchase_notes_id');
    }

    public function insumos()
    {
        return $this->belongsTo(Insumo::class, 'insumos_id');
    }
}
