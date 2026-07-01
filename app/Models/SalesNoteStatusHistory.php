<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sales_Note;
use App\Models\User;

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

    public function sales_note()
    {
        return $this->belongsTo(Sales_Note::class, 'sales_notes_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
