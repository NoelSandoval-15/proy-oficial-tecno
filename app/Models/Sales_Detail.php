<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sales_Note;
use App\Models\Product;

class Sales_Detail extends Model
{
    protected $table = 'sales_details';

    protected $fillable = [
        'sales_notes_id',
        'price_sale',
        'products_id',
        'amount',
    ];

    protected $casts = [
        'price_sale' => 'float',
        'amount' => 'integer',
    ];

    public function sales_note()
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id');
    }
}
