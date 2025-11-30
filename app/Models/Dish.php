<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'category',
        'price',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function dailyMenusAsAppetizer()
    {
        return $this->hasMany(DailyMenu::class, 'appetizer_id');
    }

    public function dailyMenusAsMain()
    {
        return $this->hasMany(DailyMenu::class, 'main_dish_id');
    }

    public function dailyMenusAsDrink()
    {
        return $this->hasMany(DailyMenu::class, 'drink_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}