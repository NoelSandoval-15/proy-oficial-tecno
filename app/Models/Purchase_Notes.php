<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Details_Purchases;

class Purchase_Notes extends Model
{
    protected $table = 'purchase_notes';

    protected $fillable = [
        'hour',
        'date',
        'total_price',
        'users_admin_id',
        'suppliers_id',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'users_admin_id');
    }

    public function suppliers()
    {
        return $this->belongsTo(Supplier::class, 'suppliers_id');
    }

    public function details_purchases()
    {
        return $this->hasMany(Details_Purchases::class, 'purchase_notes_id');
    }
}
