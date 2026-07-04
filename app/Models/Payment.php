<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    public const METHOD_CASH = 'cash';
    public const METHOD_QR_PAGOFACIL = 'qr_pagofacil';
    public const METHOD_CARD = 'card';
    public const METHOD_TRANSFER = 'transfer';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QR_GENERATED = 'qr_generated';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERTED = 'reverted';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'sales_note_id',
        'payment_method',
        'payment_number',
        'status',
        'amount_received',
        'change',
        'payment_date',
        'transaction_id',
        'pagofacil_transaction_id',
        'payment_method_transaction_id',
        'qr_base64',
        'checkout_url',
        'deep_link',
        'qr_content_url',
        'universal_url',
        'expiration_date',
        'paid_at',
        'callback_payload',
        'query_payload',
        'error_message',
        'generated_by',
    ];

    protected $casts = [
        'amount_received' => 'float',
        'change' => 'float',
        'payment_date' => 'datetime',
        'expiration_date' => 'datetime',
        'paid_at' => 'datetime',
        'callback_payload' => 'array',
        'query_payload' => 'array',
    ];

    protected $appends = [
        'status_label',
        'method_label',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_QR_GENERATED,
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_REVERTED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_QR_PAGOFACIL,
            self::METHOD_CARD,
            self::METHOD_TRANSFER,
        ];
    }

    public function salesNote(): BelongsTo
    {
        return $this->belongsTo(Sales_Note::class, 'sales_note_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeQrGenerated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_QR_GENERATED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeNotPaid(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID);
    }

    public function scopeActiveQr(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_QR_GENERATED,
        ]);
    }

    public function scopeForSalesNote(Builder $query, int $salesNoteId): Builder
    {
        return $query->where('sales_note_id', $salesNoteId);
    }

    public function scopePagoFacil(Builder $query): Builder
    {
        return $query->where('payment_method', self::METHOD_QR_PAGOFACIL);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQrGenerated(): bool
    {
        return $this->status === self::STATUS_QR_GENERATED;
    }

    public function isActiveQr(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_QR_GENERATED,
        ], true);
    }

    public function hasQr(): bool
    {
        return filled($this->qr_base64);
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null
            && now()->greaterThan($this->expiration_date)
            && !$this->isPaid();
    }

    public function getQrImageSrcAttribute(): ?string
    {
        if (!$this->hasQr()) {
            return null;
        }

        return 'data:image/png;base64,' . $this->qr_base64;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_QR_GENERATED => 'QR generado',
            self::STATUS_PAID => 'Pagado',
            self::STATUS_FAILED => 'Fallido',
            self::STATUS_REVERTED => 'Revertido',
            self::STATUS_CANCELLED => 'Anulado',
            self::STATUS_EXPIRED => 'Expirado',
            default => 'Sin estado',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_QR_PAGOFACIL => 'QR PagoFácil',
            self::METHOD_CARD => 'Tarjeta',
            self::METHOD_TRANSFER => 'Transferencia',
            default => $this->payment_method ?: 'No definido',
        };
    }

    public function registerQrResponse(array $response, string $paymentNumber): void
    {
        $values = data_get($response, 'values', []);

        $this->forceFill([
            'payment_number' => $paymentNumber,
            'status' => self::STATUS_QR_GENERATED,
            'transaction_id' => data_get($values, 'transactionId'),
            'pagofacil_transaction_id' => data_get($values, 'transactionId'),
            'payment_method_transaction_id' => data_get($values, 'paymentMethodTransactionId'),
            'qr_base64' => data_get($values, 'qrBase64'),
            'checkout_url' => data_get($values, 'checkoutUrl'),
            'deep_link' => data_get($values, 'deepLink'),
            'qr_content_url' => data_get($values, 'qrContentUrl'),
            'universal_url' => data_get($values, 'universalUrl'),
            'expiration_date' => data_get($values, 'expirationDate'),
            'error_message' => null,
        ])->save();
    }

    public function markAsPaid(?float $amount = null, ?array $payload = null): void
    {
        $total = $amount ?? $this->salesNote?->total_price ?? $this->amount_received ?? 0;

        $this->forceFill([
            'status' => self::STATUS_PAID,
            'amount_received' => round((float) $total, 2),
            'change' => 0,
            'payment_date' => now(),
            'paid_at' => now(),
            'callback_payload' => $payload ?: $this->callback_payload,
            'error_message' => null,
        ])->save();

        if ($this->salesNote && !$this->salesNote->isPaid()) {
            $this->salesNote->markAsPaid($this->generated_by);
        }
    }

    public function markAsFailed(string $message, ?array $payload = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'query_payload' => $payload ?: $this->query_payload,
        ])->save();
    }

    public function markAsExpired(): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_EXPIRED,
        ])->save();
    }
}
