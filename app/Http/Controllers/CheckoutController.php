<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function create(Cart $cart)
    {
        if ($cart->count() === 0) {
            return redirect()->route('cart.index');
        }

        return view('checkout.create', [
            'items' => $cart->items(),
            'total' => $cart->total(),
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\+?\d{10,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'delivery' => ['required', 'in:' . implode(',', Order::DELIVERY)],
            'address' => ['required_unless:delivery,pickup', 'nullable', 'string', 'max:255'],
            'payment' => ['required', 'in:' . implode(',', Order::PAYMENT)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $raw = $cart->raw();
        if (!$raw) {
            return redirect()->route('cart.index');
        }

        $order = DB::transaction(function () use ($raw, $data, $request) {
            // Блокируем строки товаров, чтобы два покупателя не купили один и тот же остаток
            $products = Product::active()->whereIn('id', array_keys($raw))->lockForUpdate()->get()->keyBy('id');

            $total = 0;
            $lines = [];
            foreach ($raw as $productId => $qty) {
                $product = $products->get($productId);
                if (!$product || $product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => __('shop.not_enough_stock', ['name' => $product?->name ?? '#' . $productId]),
                    ]);
                }
                $lines[] = [$product, $qty];
                $total += (float) $product->price * $qty;
            }

            $order = Order::create($data + [
                'number' => 'MM' . now()->format('ymd') . strtoupper(Str::random(5)),
                'user_id' => $request->user()?->id,
                'total' => $total,
                'status' => 'new',
            ]);

            foreach ($lines as [$product, $qty]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name_uk,
                    'price' => $product->price,
                    'quantity' => $qty,
                ]);
                $product->decrement('stock', $qty);
            }

            return $order;
        });

        $cart->clear();

        return redirect()->route('checkout.done')->with('order_number', $order->number);
    }

    public function done()
    {
        $number = session('order_number');
        if (!$number) {
            return redirect()->route('home');
        }

        return view('checkout.done', ['number' => $number]);
    }
}
