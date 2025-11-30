<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'menu_id',
        'reservation_date',
        'pickup_time_slot',
        'status',
        'code',
        'qr_code_path',
        'expired_at',
        'payment_method',
        'payment_status',
        'payment_proof_url',
        'delivered_at',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'pickup_time_slot' => 'datetime',
        'expired_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menu()
    {
        return $this->belongsTo(DailyMenu::class, 'menu_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', now()->toDateString());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByTimeSlot($query, $timeSlot)
    {
        return $query->where('pickup_time_slot', $timeSlot);
    }

    public function getQrCodeUrlAttribute()
    {
        if (!$this->qr_code_path) {
            return null;
        }
        return asset('storage/' . $this->qr_code_path);
    }

    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->expired_at && now()->gte($this->expired_at));
    }

    public function canBeCancelled()
    {
        return $this->status === 'pending' && 
               $this->reservation_date->isToday();
    }

    public function markAsDelivered()
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
        
        return $this;
    }

    public function cancel()
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        \DB::transaction(function () {
            $this->status = 'cancelled';
            $this->save();
            
            $this->menu->increment('stock');
        });
        
        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (!$reservation->code) {
                $reservation->code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'KL-' . strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}