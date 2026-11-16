<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    // RF-014: la solicitud de pago se acepta solo dentro de este plazo desde que se genera
    public const PAYMENT_WINDOW_MINUTES = 3;

    public const METHOD_LABELS = [
        'yape' => 'Yape / Plin',
        'plin' => 'Yape / Plin',
        'credit_card' => 'Tarjeta',
        'cash' => 'Efectivo',
    ];

    public static function methodLabel(?string $method): string
    {
        return self::METHOD_LABELS[$method] ?? 'Efectivo';
    }

    // ¿La solicitud de pago (Yape/Plin o tarjeta) ya venció?
    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    protected $fillable = [
        'reservation_id',
        'amount',
        'method',
        'status',
        'transaction_code',
        'payment_proof_url',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsCompleted($transactionCode = null)
    {
        $this->status = 'completed';
        if ($transactionCode) {
            $this->transaction_code = $transactionCode;
        }
        $this->save();

        $this->reservation->update(['payment_status' => 'paid']);
    }

    public function markAsFailed()
    {
        $this->status = 'failed';
        $this->save();

        $this->reservation->update(['payment_status' => 'failed']);
    }

    public static function generateTransactionCode()
    {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}