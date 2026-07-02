<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RawMaterial;
use App\Events\OrderPlaced;
use App\Events\OrderReady;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    // ─────────────────────────────────────────
    // PLACE ORDER
    // ─────────────────────────────────────────

    public function placeOrder(array $data, string $placedBy, int $placerId): Order
    {
        return DB::transaction(function () use ($data, $placedBy, $placerId) {

            // 1. Build order
            $order = Order::create([
                'user_id'          => $placedBy === 'staff' ? $placerId : null,
                'customer_id'      => $placedBy === 'customer' ? $placerId : null,
                'customer_type'    => $data['customer_type'],  // walkin or online
                'payment_method'   => $data['payment_method'],
                'customer_name'    => $data['customer_name'] ?? null,
                'customer_phone'   => $data['customer_phone'] ?? null,
                'note'             => $data['note'] ?? null,
                'status'           => 'pending',
                'subtotal'         => 0,
                'discount'         => $data['discount'] ?? 0,
                'total'            => 0,
            ]);

            $subtotal = 0;

            // 2. Attach items + deduct stock
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product #{$item['product_id']} not found.");
                }

                $price    = $product->is_price_open ? $item['price'] : $product->price;
                $qty      = $item['qty'];
                $itemTotal = $price * $qty;
                $subtotal += $itemTotal;

                // Snapshot item
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $product->id,
                    'name'        => $product->name,
                    'price'       => $price,
                    'qty'         => $qty,
                    'subtotal'    => $itemTotal,
                    'cook_status' => 'pending',
                ]);

                // Deduct raw material stock
                $this->deductStock($product->id, $qty);
            }

            // 3. Update totals
            $total = $subtotal - $order->discount;
            $order->update(['subtotal' => $subtotal, 'total' => $total]);

            // 4. Broadcast to all staff except placer
            broadcast(new OrderPlaced($order->load('items'), $placedBy, $placerId))->toOthers();

            return $order;
        });
    }


    // ─────────────────────────────────────────
    // STOCK DEDUCTION
    // ─────────────────────────────────────────

    private function deductStock(int $productId, int $qty): void
    {
        $recipes = Recipe::where('product_id', $productId)->get();

        foreach ($recipes as $recipe) {
            $material = RawMaterial::lockForUpdate()->find($recipe->material_id);
            if (!$material) continue;

            $needed = $recipe->qty * $qty;
            if ($material->stock < $needed) {
                throw new \Exception("Insufficient stock for material: {$material->name}");
            }

            $material->stock -= $needed;
            $material->save();
        }
    }


    // ─────────────────────────────────────────
    // STOCK REVERSAL
    // ─────────────────────────────────────────

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $recipes = Recipe::where('product_id', $item->product_id)->get();

            foreach ($recipes as $recipe) {
                $material = RawMaterial::lockForUpdate()->find($recipe->material_id);
                if (!$material) continue;

                $material->stock += $recipe->qty * $item->qty;
                $material->save();
            }
        }
    }


    // ─────────────────────────────────────────
    // CANCEL ORDER (CEO ONLY)
    // ─────────────────────────────────────────

    public function cancelOrder(int $orderId): bool
{
    return DB::transaction(function () use ($orderId) {
        $order = Order::with('items')->lockForUpdate()->find($orderId);

        if (!$order) return false;

        $this->restoreStock($order);
        $order->delete();

        return true;
    });
}


    // ─────────────────────────────────────────
    // COOK — CLAIM ITEM
    // ─────────────────────────────────────────

    public function claimItem(int $itemId, int $cookId): bool
    {
        $item = OrderItem::where('id', $itemId)
            ->where('cook_status', 'pending')
            ->first();

        if (!$item) return false;

        return $item->update([
            'cook_id'     => $cookId,
            'cook_status' => 'cooking',
            'started_at'  => now(),
        ]);
    }


    // ─────────────────────────────────────────
    // COOK — MARK ITEM DONE
    // ─────────────────────────────────────────

    // ─────────────────────────────────────────
// COOK — MARK ITEM DONE
// ─────────────────────────────────────────

public function markItemDone(int $itemId, int $cookId): bool
{
    return DB::transaction(function () use ($itemId, $cookId) {
        $item = OrderItem::where('id', $itemId)
            ->where('cook_id', $cookId)
            ->where('cook_status', 'cooking')
            ->lockForUpdate()
            ->first();

        if (!$item) return false;

        $item->update([
            'cook_status'  => 'done',
            'completed_at' => now(),
        ]);

        // Check if all items done — if yes, alert authorized staff
        $order = Order::with('items')->find($item->order_id);
        $allDone = $order->items->every(fn($i) => $i->cook_status === 'done');

        if ($allDone) {
            // Broadcast alert — NOT flipping order status yet
            broadcast(new OrderAllItemsDone($order))->toOthers();
        } else {
            $order->update(['status' => 'cooking']);
        }

        return true;
    });
}


// ─────────────────────────────────────────
// SUPERVISOR/CEO/PLACER — MARK ORDER READY
// ─────────────────────────────────────────

public function markOrderReady(int $orderId): bool
{
    return DB::transaction(function () use ($orderId) {
        $user  = Auth::guard('web')->user();
        $order = Order::with('items')->lockForUpdate()->find($orderId);

        if (!$order) return false;

        // All items must be done first
        $allDone = $order->items->every(fn($i) => $i->cook_status === 'done');
        if (!$allDone) return false;

        // Only CEO, supervisor, or staff who placed the order
        $canMark = $user->isCeo()
            || $user->isSupervisor()
            || $order->user_id === $user->id;

        if (!$canMark) return false;

        $order->update([
            'status'   => 'ready',
            'ready_at' => now(),
        ]);

        // Now broadcast ready to whoever placed it
        broadcast(new OrderReady($order))->toOthers();

        return true;
    });
}


    // ─────────────────────────────────────────
    // CASHIER — COMPLETE ORDER
    // ─────────────────────────────────────────

    public function completeOrder(int $orderId): bool
    {
        $order = Order::where('id', $orderId)
            ->where('status', 'ready')
            ->first();

        if (!$order) return false;

        return $order->update(['status' => 'completed']);
    }


    // ─────────────────────────────────────────
    // CONFIRM PAYMENT
    // ─────────────────────────────────────────

    public function confirmPayment(int $orderId, array $data): Payment
    {
        return Payment::create([
            'order_id'     => $orderId,
            'method'       => $data['method'],
            'amount'       => $data['amount'],
            'reference'    => $data['reference'] ?? null,
            'status'       => 'confirmed',
            'confirmed_by' => Auth::id(),
        ]);
    }


    // ─────────────────────────────────────────
    // QUERIES
    // ─────────────────────────────────────────

    public function activeOrders()
    {
        return Order::with('items.product')
            ->whereIn('status', ['pending', 'cooking'])
            ->orderBy('created_at')
            ->get();
    }

    public function allOrders()
    {
        return Order::with('items', 'payment')
            ->orderByDesc('created_at')
            ->get();
    }

    public function trackOrder(int $orderId)
    {
        return Order::with('items')->where('id', $orderId)->first();
    }

    public function orderHistory(int $customerId)
    {
        return Order::with('items')->where('customer_id', $customerId)->orderByDesc('created_at')->get();
    }
}

