<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase_Notes;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'description',
        'telephone',
        'url_photo',
    ];

    public function purchase_notes()
    {
        return $this->hasMany(Purchase_Notes::class, 'suppliers_id');
    }
}
