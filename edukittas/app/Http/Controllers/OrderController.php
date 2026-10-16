<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(protected OrderService $service) {}


    // ─────────────────────────────────────────
    // PLACE ORDER
    // ─────────────────────────────────────────

    public function store(OrderRequest $request)
    {
        $guard    = Auth::guard('customer')->check() ? 'customer' : 'web';
        $placedBy = $guard === 'customer' ? 'customer' : 'staff';
        $placerId = Auth::guard($guard)->id();

        $order = $this->service->placeOrder($request->validated(), $placedBy, $placerId);

        return response()->json($order, 201);
    }


    // ─────────────────────────────────────────
    // ACTIVE ORDERS — cook screen
    // ─────────────────────────────────────────

    public function active()
    {
        return response()->json($this->service->activeOrders());
    }


    // ─────────────────────────────────────────
    // ALL ORDERS — CEO / Supervisor
    // ─────────────────────────────────────────

    public function index()
    {
        return response()->json($this->service->allOrders());
    }


    // ─────────────────────────────────────────
    // TRACK ORDER — customer public
    // ─────────────────────────────────────────

    public function track(int $orderId)
    {
        $order = $this->service->trackOrder($orderId);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);
        return response()->json($order);
    }


    // ─────────────────────────────────────────
    // ORDER HISTORY — logged in customer
    // ─────────────────────────────────────────

    public function history()
    {
        $customerId = Auth::guard('customer')->id();
        return response()->json($this->service->orderHistory($customerId));
    }


    // ─────────────────────────────────────────
    // COOK — CLAIM ITEM
    // ─────────────────────────────────────────

    public function claimItem(int $itemId)
    {
        $done = $this->service->claimItem($itemId, Auth::id());
        if (!$done) return response()->json(['message' => 'Item unavailable or already claimed'], 422);
        return response()->json(['claimed' => true]);
    }


    // ─────────────────────────────────────────
    // COOK — MARK ITEM DONE
    // ─────────────────────────────────────────

    public function itemDone(int $itemId)
    {
        $done = $this->service->markItemDone($itemId, Auth::id());
        if (!$done) return response()->json(['message' => 'Action not allowed'], 422);
        return response()->json(['done' => true]);
    }


    // ─────────────────────────────────────────
    // CASHIER — COMPLETE ORDER
    // ─────────────────────────────────────────

    public function complete(int $orderId)
    {
        $done = $this->service->completeOrder($orderId);
        if (!$done) return response()->json(['message' => 'Order not ready yet'], 422);
        return response()->json(['completed' => true]);
    }


    // ─────────────────────────────────────────
    // CANCEL ORDER — CEO only
    // ─────────────────────────────────────────

    public function cancel(int $orderId)
    {
        $done = $this->service->cancelOrder($orderId);
        if (!$done) return response()->json(['message' => 'Order not found or already cancelled'], 422);
        return response()->json(['cancelled' => true]);
    }


    // ─────────────────────────────────────────
    // CONFIRM PAYMENT
    // ─────────────────────────────────────────

    public function confirmPayment(PaymentRequest $request, int $orderId)
    {
        $payment = $this->service->confirmPayment($orderId, $request->validated());
        return response()->json($payment, 201);
    }
}
