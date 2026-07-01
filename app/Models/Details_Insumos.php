<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Details_Insumos extends Model
{
    protected $table = 'details_insumos';

    protected $fillable = [
        'insumos_id',
        'insumos_notes_id',
        'amount',
    ];

    public function insumos()
    {
        return $this->belongsTo(Insumo::class, 'insumos_id');
    }

    public function insumos_notes()
    {
        return $this->belongsTo(Insumos_Notes::class, 'insumos_notes_id');
    }
}
