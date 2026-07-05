<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesNoteStatusHistory extends Model
{
    protected $table = 'sales_note_status_histories';

    protected $fillable = [
        'sales_notes_id',
        'users_id',
        'status',
        'title',
        'description',
    ];

    protected $casts = [
        'sales_notes_id' => 'integer',
        'users_id' => 'integer',
    ];

    public function sales_note(): BelongsTo
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function salesNote(): BelongsTo
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function scopeForSale(Builder $query, int $salesNoteId): Builder
    {
        return $query->where('sales_notes_id', $salesNoteId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest('id');
    }
}
