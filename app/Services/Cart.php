<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Корзина в сессии: [product_id => quantity].
 * Цены всегда берутся из базы, в сессии хранятся только id и количество.
 */
class Cart
{
    private const KEY = 'cart';
    private const MAX_QTY = 99;

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(Product $product, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$product->id] = $this->clamp(($cart[$product->id] ?? 0) + $qty, $product);
        session([self::KEY => $cart]);
    }

    public function set(Product $product, int $qty): void
    {
        if ($qty < 1) {
            $this->remove($product->id);
            return;
        }
        $cart = $this->raw();
        $cart[$product->id] = $this->clamp($qty, $product);
        session([self::KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, array{product: Product, quantity: int, subtotal: float}> */
    public function items(): Collection
    {
        $cart = $this->raw();
        if (!$cart) {
            return collect();
        }

        return Product::active()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(fn (Product $p) => [
                'product' => $p,
                'quantity' => $cart[$p->id],
                'subtotal' => (float) $p->price * $cart[$p->id],
            ]);
    }

    public function total(): float
    {
        return (float) $this->items()->sum('subtotal');
    }

    private function clamp(int $qty, Product $product): int
    {
        return max(1, min($qty, self::MAX_QTY, max($product->stock, 1)));
    }
}
