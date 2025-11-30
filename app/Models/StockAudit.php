<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAudit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'menu_id',
        'old_stock',
        'new_stock',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'old_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    public function menu()
    {
        return $this->belongsTo(DailyMenu::class, 'menu_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByMenu($query, $menuId)
    {
        return $query->where('menu_id', $menuId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getStockChangeAttribute()
    {
        return $this->new_stock - $this->old_stock;
    }

    public function getChangeTypeAttribute()
    {
        $change = $this->stock_change;
        
        if ($change > 0) {
            return 'increase';
        } elseif ($change < 0) {
            return 'decrease';
        }
        
        return 'no_change';
    }
}
