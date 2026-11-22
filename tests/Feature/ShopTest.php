<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderPlaced;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock = 5): Product
    {
        $cat = Category::create(['slug' => 'test', 'name_uk' => 'Тест', 'name_ru' => 'Тест', 'sort' => 1]);

        return Product::create([
            'category_id' => $cat->id, 'sku' => 'T-1', 'slug' => 'test-product',
            'name_uk' => 'Тестовий товар', 'name_ru' => 'Тестовый товар',
            'price' => 500, 'stock' => $stock, 'is_active' => true,
        ]);
    }

    public function test_public_pages_open(): void
    {
        $p = $this->product();
        $this->get('/')->assertOk();
        $this->get('/catalog')->assertOk();
        $this->get('/')->assertSee('theme-toggle', false);
        $this->get(route('product', $p))->assertOk()->assertSee('application/ld+json', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('test-product');
        // robots.txt отдаёт веб-сервер из public, а не Laravel — проверяем, что файл на месте
        $this->assertFileExists(public_path('robots.txt'));
    }

    public function test_admin_access_rules(): void
    {
        $this->get('/admin')->assertRedirect();
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin')->assertOk();
    }

    public function test_checkout_creates_order_sends_mails_and_decrements_stock(): void
    {
        Mail::fake();
        config(['shop.novaposhta.api_key' => null]);
        Setting::put('notify_email', 'owner@example.com');
        $p = $this->product(5);

        $this->withSession(['cart' => [$p->id => 2]])->post('/checkout', [
            'first_name' => 'Иван', 'last_name' => 'Петров', 'phone' => '0501234567',
            'delivery' => 'ukrposhta', 'payment' => 'cod', 'up_city' => 'Киев', 'up_index' => '01001',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertMatchesRegularExpression('/^\d+$/', $order->number);
        $this->assertSame(3, $p->fresh()->stock);
        Mail::assertSent(NewOrderAdmin::class);
    }

    public function test_card_payment_order_can_be_marked_paid(): void
    {
        Mail::fake();
        config(['shop.novaposhta.api_key' => null]);
        $p = $this->product(5);

        $this->withSession(['cart' => [$p->id => 1]])->post('/checkout', [
            'first_name' => 'Иван', 'last_name' => 'Петров', 'phone' => '0501234567',
            'delivery' => 'ukrposhta', 'payment' => 'card', 'up_city' => 'Киев', 'up_index' => '01001',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('card', $order->payment);
        $this->assertNull($order->paid_at);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.orders.paid', $order))->assertRedirect();
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_deleted_product_disappears_from_cart_counter(): void
    {
        $p = $this->product(5);
        $session = ['cart' => [$p->id => 2, 9999 => 1]]; // 9999 — товара уже нет

        $cart = $this->withSession($session)->app->make(\App\Services\Cart::class);
        $this->assertSame(2, $cart->count());
        $this->assertArrayNotHasKey(9999, $cart->raw());
    }

    public function test_review_is_pending_until_approved(): void
    {
        $p = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reviews.store', $p), ['rating' => 5, 'body' => 'Отлично'])->assertRedirect();

        $review = Review::first();
        $this->assertSame('pending', $review->status);
        $this->assertCount(0, $p->approvedReviews);
    }

    public function test_favorites_toggle(): void
    {
        $p = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $p));
        $this->assertCount(1, $user->favorites()->get());
        $this->actingAs($user)->post(route('favorites.toggle', $p));
        $this->assertCount(0, $user->favorites()->get());
    }
}
