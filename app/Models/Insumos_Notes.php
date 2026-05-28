<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Details_Insumos;

class Insumos_Notes extends Model
{
    protected $table = 'insumos_notes';

    protected $fillable = [
        'hour',
        'date',
        'users_admin_id',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'users_admin_id');
    }

    public function details_insumos()
    {
        return $this->hasMany(Details_Insumos::class, 'insumos_notes_id');
    }
}
