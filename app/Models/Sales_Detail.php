<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sales_Detail extends Model
{
    protected $table = 'sales_details';

    protected $fillable = [
        'sales_notes_id',
        'products_id',
        'amount',
        'price_sale',
    ];

    protected $casts = [
        'amount' => 'integer',
        'price_sale' => 'float',
    ];

    protected $appends = [
        'subtotal',
    ];

    public function sales_note(): BelongsTo
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'products_id');
    }

    public function getSubtotalAttribute(): float
    {
        return round(((float) $this->price_sale) * ((int) $this->amount), 2);
    }
}
