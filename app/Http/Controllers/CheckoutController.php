<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\NovaPoshta;
use Illuminate\Validation\Rule;
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
            'npEnabled' => NovaPoshta::enabled(),
        ]);
    }

    public function store(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\\p{L}'’\\- ]+$/u"],
            'last_name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\\p{L}'’\\- ]+$/u"],
            'phone' => ['required', 'regex:/^\+?\d{10,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'delivery' => ['required', 'in:' . implode(',', Order::DELIVERY)],
            'payment' => ['required', 'in:' . implode(',', Order::PAYMENT)],
            'comment' => ['nullable', 'string', 'max:1000'],
            // Новая Почта: город и отделение (если подключён API — только выбранные из списка)
            'np_city' => ['required_if:delivery,nova_poshta', 'nullable', 'string', 'max:150'],
            'np_warehouse' => ['required_if:delivery,nova_poshta', 'nullable', 'string', 'max:255'],
            'np_city_ref' => [Rule::requiredIf(fn () => $request->input('delivery') === 'nova_poshta' && NovaPoshta::enabled()), 'nullable', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_warehouse_ref' => [Rule::requiredIf(fn () => $request->input('delivery') === 'nova_poshta' && NovaPoshta::enabled()), 'nullable', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_is_postomat' => ['nullable', 'boolean'],
            // Новая Почта, адресная доставка (только из списков НП)
            'npa_city' => ['required_if:delivery,np_address', 'nullable', 'string', 'max:150'],
            'npa_city_ref' => ['required_if:delivery,np_address', 'nullable', 'regex:/^[0-9a-f-]{36}$/i'],
            'npa_street' => ['required_if:delivery,np_address', 'nullable', 'string', 'max:255'],
            'npa_street_ref' => ['required_if:delivery,np_address', 'nullable', 'regex:/^[0-9a-f-]{36}$/i'],
            'npa_building' => ['required_if:delivery,np_address', 'nullable', 'string', 'max:20'],
            'npa_flat' => ['nullable', 'string', 'max:20'],
            // Укрпошта: город и индекс отделения
            'up_city' => ['required_if:delivery,ukrposhta', 'nullable', 'string', 'max:150'],
            'up_index' => ['required_if:delivery,ukrposhta', 'nullable', 'regex:/^\d{5}$/'],
        ]);

        // Новая Почта принимает имена только кириллицей — просим клиента сразу
        if (in_array($data['delivery'], ['nova_poshta', 'np_address'], true)) {
            foreach (['first_name', 'last_name'] as $f) {
                if (!preg_match("/^[\p{Cyrillic}'’\- ]+$/u", $data[$f])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([$f => __('shop.name_cyrillic')]);
                }
            }
        }

        // В заказе хранится полное имя: «Имя Фамилия»
        $data['name'] = trim($data['first_name']) . ' ' . trim($data['last_name']);
        unset($data['first_name'], $data['last_name']);

        if ($data['delivery'] === 'nova_poshta') {
            $delivery = [
                'city' => $data['np_city'],
                'branch' => $data['np_warehouse'],
                'np_city_ref' => $data['np_city_ref'] ?? null,
                'np_warehouse_ref' => $data['np_warehouse_ref'] ?? null,
                'np_is_postomat' => !empty($data['np_is_postomat']),
            ];
        } elseif ($data['delivery'] === 'np_address') {
            $flat = trim((string) ($data['npa_flat'] ?? ''));
            $delivery = [
                'city' => $data['npa_city'],
                'branch' => $data['npa_street'] . ', ' . $data['npa_building'] . ($flat !== '' ? ', кв. ' . $flat : ''),
                'np_city_ref' => $data['npa_city_ref'],
                'np_street_ref' => $data['npa_street_ref'],
                'np_street' => $data['npa_street'],
                'np_building' => $data['npa_building'],
                'np_flat' => $flat !== '' ? $flat : null,
            ];
        } else {
            $delivery = [
                'city' => $data['up_city'],
                'branch' => $data['up_index'],
            ];
        }
        $delivery['address'] = $delivery['city'] . ', ' . ($data['delivery'] === 'ukrposhta' ? 'індекс ' : '') . $delivery['branch'];

        $data = array_diff_key($data, array_flip(['np_city', 'np_warehouse', 'np_city_ref', 'np_warehouse_ref', 'np_is_postomat', 'npa_city', 'npa_city_ref', 'npa_street', 'npa_street_ref', 'npa_building', 'npa_flat', 'up_city', 'up_index'])) + $delivery;

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
                'number' => 'tmp' . Str::random(20), // временно, ниже заменяется на цифровой номер
                'user_id' => $request->user()?->id,
                'total' => $total,
                'status' => 'new',
                'locale' => app()->getLocale(),
            ]);

            // Номер заказа — только цифры (1001, 1002, ...)
            $order->update(['number' => (string) (1000 + $order->id)]);

            foreach ($lines as [$product, $qty]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name_uk,
                    'image' => $product->images[0] ?? null,
                    'price' => $product->price,
                    'quantity' => $qty,
                ]);
                $product->decrement('stock', $qty);
            }

            return $order;
        });

        $cart->clear();

        // Письмо клиенту (если почта не настроена или упала — заказ всё равно оформлен)
        \App\Services\OrderMail::placed($order);

        return redirect()->route('checkout.done')->with('order_number', $order->number)->with('order_payment', $order->payment);
    }

    public function done()
    {
        $number = session('order_number');
        if (!$number) {
            return redirect()->route('home');
        }

        return view('checkout.done', ['number' => $number, 'payment' => session('order_payment')]);
    }
}
