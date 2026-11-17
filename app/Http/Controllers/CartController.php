<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        return view('cart.index', [
            'items' => $cart->items(),
            'total' => $cart->total(),
        ]);
    }

    public function add(Request $request, Cart $cart, Product $product)
    {
        abort_unless($product->is_active && $product->in_stock, 404);
        $cart->add($product, max(1, (int) $request->input('quantity', 1)));

        return back()->with('status', __('shop.added_to_cart'));
    }

    public function update(Request $request, Cart $cart, Product $product)
    {
        $cart->set($product, (int) $request->input('quantity', 1));

        return back();
    }

    public function remove(Cart $cart, int $productId)
    {
        $cart->remove($productId);

        return back();
    }
}
