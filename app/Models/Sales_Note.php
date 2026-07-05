<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected $appends = [
        'status_label',
        'order_type_label',
        'can_generate_payment',
        'total_items',
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
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
        ];
    }

    public static function payableStatuses(): array
    {
        return [
            self::STATUS_DELIVERED,
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
            self::STATUS_PAID => [
                'title' => 'Pedido pagado',
                'description' => 'El pago fue confirmado correctamente.',
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
            self::STATUS_PAID => 5,
            self::STATUS_CANCELLED => 99,
        ];
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePayable(Builder $query): Builder
    {
        return $query->whereIn('status', self::payableStatuses());
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('users_client_id', $clientId);
    }

    public function scopeWithPaymentData(Builder $query): Builder
    {
        return $query->with([
            'users_client.profile',
            'users_admin.profile',
            'tables',
            'reservations',
            'details.product',
            'latestPayment',
            'paidPayment',
            'activePayment',
        ]);
    }

    public function users_admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_admin_id');
    }

    public function users_client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_client_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_admin_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_client_id');
    }

    public function tables(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'tables_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'tables_id');
    }

    public function reservations(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }

    public function sales_details(): HasMany
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }

    public function sales_notes(): HasMany
    {
        return $this->hasMany(Sales_Detail::class, 'sales_notes_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'sales_note_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'sales_note_id')->latestOfMany();
    }

    public function activePayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'sales_note_id')
            ->whereIn('status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_QR_GENERATED,
            ])
            ->latestOfMany();
    }

    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'sales_note_id')
            ->where('status', Payment::STATUS_PAID)
            ->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SalesNoteStatusHistory::class, 'sales_notes_id')
            ->oldest('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canGeneratePayment(): bool
    {
        return $this->isDelivered()
            && !$this->hasPaidPayment()
            && !$this->hasActivePaymentQr();
    }

    public function hasPaidPayment(): bool
    {
        if ($this->relationLoaded('paidPayment')) {
            return $this->paidPayment !== null;
        }

        if ($this->relationLoaded('payments')) {
            return $this->payments->contains('status', Payment::STATUS_PAID);
        }

        return $this->payments()
            ->where('status', Payment::STATUS_PAID)
            ->exists();
    }

    public function hasActivePaymentQr(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(function (Payment $payment) {
                return $payment->status === Payment::STATUS_QR_GENERATED
                    && filled($payment->qr_base64)
                    && !$payment->isExpired();
            });
        }

        if ($this->relationLoaded('activePayment')) {
            return $this->activePayment !== null
                && $this->activePayment->status === Payment::STATUS_QR_GENERATED
                && filled($this->activePayment->qr_base64)
                && !$this->activePayment->isExpired();
        }

        return $this->payments()
            ->where('status', Payment::STATUS_QR_GENERATED)
            ->whereNotNull('qr_base64')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            })
            ->exists();
    }

    public function getTotalItemsAttribute(): int
    {
        if ($this->relationLoaded('details')) {
            return (int) $this->details->sum('amount');
        }

        return (int) $this->details()->sum('amount');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ?: 'Sin estado';
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return $this->order_type ?: 'No definido';
    }

    public function getCanGeneratePaymentAttribute(): bool
    {
        return $this->canGeneratePayment();
    }

    public function markAsPaid(?int $userId = null): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_PAID,
        ])->save();

        $this->recordStatusHistory(
            userId: $userId,
            status: self::STATUS_PAID,
            title: 'Pedido pagado',
            description: 'El pago fue confirmado correctamente.'
        );
    }

    public function markAsDelivered(?int $userId = null): void
    {
        if ($this->isPaid() || $this->isCancelled() || $this->isDelivered()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_DELIVERED,
        ])->save();

        $this->recordStatusHistory(
            userId: $userId,
            status: self::STATUS_DELIVERED,
            title: 'Pedido entregado',
            description: 'El pedido fue entregado correctamente.'
        );
    }

    private function recordStatusHistory(
        ?int $userId,
        string $status,
        string $title,
        string $description
    ): void {
        if (!$userId) {
            return;
        }

        $alreadyExists = $this->statusHistories()
            ->where('status', $status)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $this->statusHistories()->create([
            'users_id' => $userId,
            'status' => $status,
            'title' => $title,
            'description' => $description,
        ]);
    }
}
