<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

  public function broadcastOn(): array
{
    if ($this->order->user_id) {
        return [new PrivateChannel('staff.' . $this->order->user_id)];
    }

    if ($this->order->customer_id) {
        return [new PrivateChannel('customer.' . $this->order->customer_id)];
    }

    // Guest — channel identified by their localStorage token
    return [new Channel('guest.' . $this->order->guest_token)];
}

    public function broadcastAs(): string
    {
        return 'order.ready';
    }

    public function broadcastWith(): array
    {
        return ['order' => $this->order];
    }
}