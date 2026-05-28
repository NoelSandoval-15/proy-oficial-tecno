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

    public function details_insumos()
    {
        return $this->hasMany(Details_Insumos::class, 'insumos_id');
    }

    public function details_purchases()
    {
        return $this->hasMany(Details_Purchases::class, 'insumos_id');
    }
}
