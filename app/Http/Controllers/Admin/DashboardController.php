<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'newOrders' => Order::where('status', 'new')->count(),
            'salesToday' => Order::whereDate('created_at', today())->where('status', '!=', 'canceled')->sum('total'),
            'salesMonth' => Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->where('status', '!=', 'canceled')->sum('total'),
            'lowStock' => Product::where('stock', '<', 5)->count(),
            'latestOrders' => Order::latest()->take(8)->get(),
        ]);
    }
}
