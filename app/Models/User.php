<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Theme;
use App\Models\Reservation;
use App\Models\Sales_Note;
use App\Models\Insumos_Notes;
use App\Models\Purchase_Notes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'themes_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'themes_id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'users_id');
    }

    public function reservation()
    {
        return $this->hasMany(Reservation::class, 'users_id');
    }

    public function sales_notes_admin()
    {
        return $this->hasMany(Sales_Note::class, 'users_admin_id');
    }

    public function sales_notes_client()
    {
        return $this->hasMany(Sales_Note::class, 'users_client_id');
    }

    public function insumos_notes()
    {
        return $this->hasMany(Sales_Note::class, 'users_admin_id');
    }

    public function purchase_notes()
    {
        return $this->hasMany(Purchase_Notes::class, 'users_admin_id');
    }
}
