<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Table;
use App\Models\Reservation;
use App\Models\Sales_Detail;

class Sales_Note extends Model
{
    protected $table = 'sales_notes';

    protected $fillable = [
        'hour',
        'date',
        'total_price',
        'status',
        'users_admin_id',
        'users_client_id',
        'tables_id',
        'reservations_id',
    ];

    public function users_admin()
    {
        return $this->belongsTo(User::class, 'users_admin_id');
    }

    public function users_client()
    {
        return $this->belongsTo(User::class, 'users_client_id');
    }

    public function tables()
    {
        return $this->belongsTo(Table::class, 'tables_id');
    }

    public function reservations()
    {
        return $this->belongsTo(Reservation::class, 'reservations_id');
    }

    public function sales_notes()
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }
}
