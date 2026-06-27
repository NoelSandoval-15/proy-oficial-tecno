<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Details_Reservation;
use App\Models\User;
use App\Models\Sales_Note;

class Reservation extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'descriptions',
        'hour',
        'date',
        'state',
        'users_id',
        'users_cliente_id',
        
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
    public function users_cliente_id()
    {
        return $this->belongsTo(User::class, 'users_cliente_id');
    }

    public function details_reservations()
    {
        return $this->hasMany(Details_Reservation::class, 'reservations_id');
    }

    public function sales_notes()
    {
        return $this->hasMany(Sales_Note::class, 'reservations_id');
    }
}
