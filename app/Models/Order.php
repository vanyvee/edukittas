<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'customer_id', 'order_type',
        'status', 'notes', 'total', 'ready_at'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'ready_at' => 'datetime',
    ];

    // Who placed it
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // The actual placer regardless of type
    public function placer()
    {
        return $this->user ?? $this->customer;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function isFullyCooked(): bool
    {
        return $this->items()->where('cook_status', '!=', 'done')->doesntExist();
    }
}
