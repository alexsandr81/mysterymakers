<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Модератору дашборд (выручка, заказы) не показываем
        if (! $request->user()->is_admin) {
            return redirect()->route('admin.products.index');
        }

        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $from = today()->subDays($days - 1);

        // Заказы за период (без отменённых) — суммируем и группируем по дням
        $orders = Order::where('created_at', '>=', $from)->where('status', '!=', 'canceled')
            ->get(['id', 'total', 'created_at']);

        $byDay = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'));
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $dayOrders = $byDay->get($date->format('Y-m-d'), collect());
            $series[] = [
                'label' => $date->format('d.m'),
                'revenue' => (float) $dayOrders->sum('total'),
                'count' => $dayOrders->count(),
            ];
        }
        $maxRevenue = max(1, collect($series)->max('revenue'));
        foreach ($series as &$point) {
            $point['height'] = $point['revenue'] > 0 ? max(4, round($point['revenue'] / $maxRevenue * 100)) : 0;
        }
        unset($point);

        $revenue = (float) $orders->sum('total');

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'canceled')
            ->where('orders.created_at', '>=', $from)
            ->selectRaw('order_items.name as name, sum(order_items.quantity) as qty, sum(order_items.price * order_items.quantity) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $statusCounts = Order::where('created_at', '>=', $from)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.dashboard', [
            'days' => $days,
            'series' => $series,
            'ordersCount' => $orders->count(),
            'revenue' => $revenue,
            'avgCheck' => $orders->count() ? $revenue / $orders->count() : 0,
            'topProducts' => $topProducts,
            'statusCounts' => $statusCounts,
            'newOrders' => Order::where('status', 'new')->count(),
            'salesToday' => Order::whereDate('created_at', today())->where('status', '!=', 'canceled')->sum('total'),
            'pendingReviews' => Review::where('status', 'pending')->count(),
            'lowStockProducts' => Product::where('stock', '<', 5)->orderBy('stock')->take(8)->get(),
            'latestOrders' => Order::latest()->take(8)->get(),
        ]);
    }
}
