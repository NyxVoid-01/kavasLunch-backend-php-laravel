<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reservation_id',
        'amount',
        'method',
        'status',
        'transaction_code',
        'payment_proof_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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