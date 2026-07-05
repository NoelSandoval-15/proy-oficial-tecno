<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Details_Insumos;
use App\Models\Details_Purchases;

class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'name',
        'amount',
        'price',
    ];

    protected $casts = [
        'amount' => 'float',
        'price' => 'float',
    ];

    protected $appends = [
        'stock_status',
        'price_label',
    ];

    public function details_insumos()
    {
        return $this->hasMany(Details_Insumos::class, 'insumos_id');
    }

    public function details_purchases()
    {
        return $this->hasMany(Details_Purchases::class, 'insumos_id');
    }

    public function getStockStatusAttribute(): string
    {
        if ((float) $this->amount <= 0) {
            return 'Sin stock';
        }

        if ((float) $this->amount <= 5) {
            return 'Stock bajo';
        }

        return 'Disponible';
    }

    public function getPriceLabelAttribute(): string
    {
        return 'Bs ' . number_format((float) $this->price, 2, ',', '.');
    }
}
