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

    /** true, если корзина уже очищена от удалённых/скрытых товаров в этом запросе */
    private bool $pruned = false;

    /**
     * Содержимое корзины. Товары, которых больше нет (удалены или скрыты),
     * сразу убираются из корзины — иначе значок «🛒 1» остаётся висеть без товара.
     */
    public function raw(): array
    {
        $cart = session(self::KEY, []);

        if ($cart && !$this->pruned) {
            $this->pruned = true;
            $alive = Product::active()->whereIn('id', array_keys($cart))->pluck('id')->all();
            $clean = array_intersect_key($cart, array_flip($alive));
            if (count($clean) !== count($cart)) {
                session([self::KEY => $clean]);
                $cart = $clean;
            }
        }

        return $cart;
    }

    public function add(Product $product, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$product->id] = $this->clamp(($cart[$product->id] ?? 0) + $qty, $product);
        session([self::KEY => $cart]);
        $this->pruned = false;
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
        $this->pruned = false;
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::KEY => $cart]);
        $this->pruned = false;
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
        $this->pruned = false;
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
            ->where('price', '>', 0)
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
