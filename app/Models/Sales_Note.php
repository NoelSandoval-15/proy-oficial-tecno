<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Table;
use App\Models\Reservation;
use App\Models\Sales_Detail;
use App\Models\SalesNoteStatusHistory;

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

    public static function ticketStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
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

    public static function ticketStatusSteps(): array
    {
        return [
            self::STATUS_PENDING => [
                'title' => 'Pedido recibido',
                'description' => 'Tu pedido fue enviado correctamente y está pendiente de atención.',
            ],
            self::STATUS_PREPARING => [
                'title' => 'Pedido en preparación',
                'description' => 'El personal comenzó a preparar tu pedido.',
            ],
            self::STATUS_READY => [
                'title' => 'Pedido listo',
                'description' => 'Tu pedido ya está listo para ser entregado.',
            ],
            self::STATUS_DELIVERED => [
                'title' => 'Pedido entregado',
                'description' => 'El pedido fue entregado correctamente.',
            ],
            self::STATUS_CANCELLED => [
                'title' => 'Pedido cancelado',
                'description' => 'El pedido fue cancelado.',
            ],
        ];
    }

    public static function ticketStatusRank(): array
    {
        return [
            self::STATUS_PENDING => 1,
            self::STATUS_PREPARING => 2,
            self::STATUS_READY => 3,
            self::STATUS_DELIVERED => 4,
            self::STATUS_CANCELLED => 99,
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

    public function statusHistories()
    {
        return $this->hasMany(SalesNoteStatusHistory::class, 'sales_notes_id')
            ->oldest();
    }
}
