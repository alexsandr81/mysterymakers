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

    /** Для автообновления счётчика и звука: сколько новых заказов и какой заказ последний */
    public function poll()
    {
        $last = Order::latest('id')->first(['id', 'number', 'total']);

        return response()->json([
            'new' => Order::where('status', 'new')->count(),
            'last_id' => $last?->id ?? 0,
            'number' => $last?->number,
            'total' => $last ? \App\Support\Money::format($last->total) : null,
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('items.product')]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', Order::STATUSES)]]);

        $changed = false;

        DB::transaction(function () use ($order, $data, &$changed) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            $changed = $order->status !== $data['status'];

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

        if ($changed) {
            \App\Services\OrderMail::statusChanged($order->fresh());
        }

        return back()->with('status', $changed ? 'Статус обновлён, клиенту отправлено письмо' : 'Статус не изменился');
    }

    /**
     * Удаление заказа. Если заказ ещё не выполнен (новый / в обработке), товары возвращаются на склад.
     * У отменённых остаток уже возвращён, у отправленных и выполненных товар считается проданным.
     */
    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (in_array($order->status, ['new', 'processing'], true)) {
                foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
                    $item->product?->increment('stock', $item->quantity);
                }
            }

            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('status', 'Заказ удалён');
    }
}
