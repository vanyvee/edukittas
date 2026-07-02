<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'cook_id',
        'name', 'price', 'quantity', 'subtotal',
        'cook_status', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cook()
    {
        return $this->belongsTo(User::class, 'cook_id');
    }

    public function markDone(int $cookId): void
    {
        $this->update([
            'cook_status' => 'done',
            'cook_id' => $cookId,
            'completed_at' => now(),
        ]);
    }
}
