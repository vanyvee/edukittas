<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'price', 'prep_time',
        'is_price_open', 'is_available', 'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_price_open' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
