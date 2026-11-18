<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $orders = Order::when(in_array($status, Order::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('items.product')]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', Order::STATUSES)]]);

        DB::transaction(function () use ($order, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            // При отмене возвращаем товар на склад (только один раз)
            if ($data['status'] === 'canceled' && $order->status !== 'canceled') {
                foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
                    $item->product?->increment('stock', $item->quantity);
                }
            }
            // Если заказ вернули из «отменён» — снова списываем
            if ($order->status === 'canceled' && $data['status'] !== 'canceled') {
                foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
                    $item->product?->decrement('stock', min($item->quantity, $item->product->stock));
                }
            }

            $order->update(['status' => $data['status']]);
        });

        return back()->with('status', 'Статус обновлён');
    }
}
