<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sales_Note;
use App\Models\Details_Reservation;

class Table extends Model
{
    protected $table = 'tables';

    protected $fillable = [
        'name',
        'amount',
        // 'state',
    ];

    public function details_reservations()
    {
        return $this->hasMany(Details_Reservation::class, 'tables_id');
    }

    public function sales_notes()
    {
        return $this->hasMany(Sales_Note::class, 'tables_id');
    }
}
