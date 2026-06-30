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
        'order_type',
        'users_admin_id',
        'users_client_id',
        'tables_id',
        'reservations_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'float',
    ];

    public const STATUS_PENDING = 'Pendiente';
    public const STATUS_PREPARING = 'En preparación';
    public const STATUS_READY = 'Listo';
    public const STATUS_DELIVERED = 'Entregado';
    public const STATUS_CANCELLED = 'Cancelado';
    public const STATUS_PAID = 'Pagado';

    public const TYPE_TABLE = 'Mesa';
    public const TYPE_TAKEAWAY = 'Para llevar';
    public const TYPE_COUNTER = 'Mostrador';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
            self::STATUS_PAID,
        ];
    }

    public static function orderTypes(): array
    {
        return [
            self::TYPE_TABLE,
            self::TYPE_TAKEAWAY,
            self::TYPE_COUNTER,
        ];
    }

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

    public function details()
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }

    public function sales_notes()
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }
}
