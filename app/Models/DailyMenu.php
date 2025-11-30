<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'date',
        'appetizer_id',
        'main_dish_id',
        'drink_id',
        'price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function appetizer()
    {
        return $this->belongsTo(Dish::class, 'appetizer_id');
    }

    public function mainDish()
    {
        return $this->belongsTo(Dish::class, 'main_dish_id');
    }

    public function drink()
    {
        return $this->belongsTo(Dish::class, 'drink_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'menu_id');
    }

    public function stockAudits()
    {
        return $this->hasMany(StockAudit::class, 'menu_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }

    public function getFullMenuAttribute()
    {
        return [
            'appetizer' => $this->appetizer,
            'main_dish' => $this->mainDish,
            'drink' => $this->drink,
        ];
    }

    public function getAvailableStockAttribute()
    {
        return $this->stock;
    }

    public function isAvailable()
    {
        return $this->is_active && $this->stock > 0;
    }

    public function hasStock(): bool
    {
        return $this->stock > 0;
    }
}