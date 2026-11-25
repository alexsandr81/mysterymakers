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

    public function test_image_urls_follow_current_host_not_app_url(): void
    {
        config(['app.url' => 'https://other.example']);

        $this->assertSame(url('storage/products/a.webp'), \App\Support\ImageStore::url('products/a.webp'));
        $this->assertStringNotContainsString('other.example', \App\Support\ImageStore::url('products/a.webp'));
    }

    public function test_admin_product_list_sorting_and_category_move(): void
    {
        $p = $this->product();
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['name', 'sku', 'category', 'price', 'stock', 'active', 'created'] as $col) {
            $this->actingAs($admin)->get(route('admin.products.index', ['sort' => $col, 'dir' => 'asc']))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.products.index', ['sort' => 'hack; drop table', 'dir' => 'x']))->assertOk();

        $a = Category::first();
        $b = Category::create(['slug' => 'second', 'name_uk' => 'Друга', 'name_ru' => 'Вторая', 'sort' => 5]);
        $this->actingAs($admin)->post(route('admin.categories.move', [$b, 'up']))->assertRedirect();
        $this->assertLessThan($a->fresh()->sort, $b->fresh()->sort);
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
    }

    public function test_catalog_shows_subcategories_in_dropdown(): void
    {
        $parent = Category::create(['slug' => 'parent', 'name_uk' => 'Батько', 'name_ru' => 'Родитель', 'sort' => 1]);
        Category::create(['slug' => 'child', 'name_uk' => 'Дитина', 'name_ru' => 'Ребёнок', 'parent_id' => $parent->id, 'sort' => 1]);

        $this->get('/catalog')->assertOk()->assertSee('tree__group', false);
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

    public function test_sku_ranges_nest_inside_category_block(): void
    {
        $top = Category::create(['slug' => 'a', 'name_uk' => 'А', 'name_ru' => 'А', 'sort' => 1, 'sku_start' => Category::nextSkuStart(null)]);
        $sub1 = Category::create(['parent_id' => $top->id, 'slug' => 'a1', 'name_uk' => 'А1', 'name_ru' => 'А1', 'sort' => 1, 'sku_start' => Category::nextSkuStart($top->id)]);
        $sub2 = Category::create(['parent_id' => $top->id, 'slug' => 'a2', 'name_uk' => 'А2', 'name_ru' => 'А2', 'sort' => 2, 'sku_start' => Category::nextSkuStart($top->id)]);
        $top2 = Category::create(['slug' => 'b', 'name_uk' => 'Б', 'name_ru' => 'Б', 'sort' => 2, 'sku_start' => Category::nextSkuStart(null)]);

        $this->assertSame([10000, 11000, 12000, 20000], [$top->sku_start, $sub1->sku_start, $sub2->sku_start, $top2->sku_start]);
        $this->assertSame('10000', $top->nextSku());
        $this->assertSame('11000', $sub1->nextSku());

        Product::create(['category_id' => $sub1->id, 'sku' => '11000', 'slug' => 'p1', 'name_uk' => 'P', 'name_ru' => 'P', 'price' => 1, 'stock' => 1, 'is_active' => true]);
        $this->assertSame('11001', $sub1->fresh()->nextSku());
        $this->assertSame('12000', $sub2->nextSku());
    }

    public function test_admin_categories_sorting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'z', 'name_uk' => 'Я', 'name_ru' => 'Я', 'sort' => 1, 'sku_start' => 10000]);
        Category::create(['parent_id' => $top->id, 'slug' => 'a', 'name_uk' => 'А', 'name_ru' => 'А', 'sort' => 2, 'sku_start' => 11000]);

        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
        foreach (['name', 'name_uk', 'parent', 'products', 'sku', 'sort'] as $col) {
            $this->actingAs($admin)->get(route('admin.categories.index', ['sort' => $col, 'dir' => 'desc']))->assertOk();
        }
    }

    public function test_drive_folders_are_optional_and_created_when_configured(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $row = ['name_uk' => 'Коробки', 'name_ru' => 'Коробки', 'sort' => 1];

        // Диск не настроен — категория всё равно создаётся
        config(['shop.drive.root_id' => null]);
        $this->actingAs($admin)->post(route('admin.categories.store'), $row)->assertRedirect();
        $this->assertNull(Category::first()->drive_folder_id);

        // Диск настроен (токен подставлен в кэш, запросы к Google подменены)
        $key = tempnam(sys_get_temp_dir(), 'gd');
        file_put_contents($key, '{}');
        config(['shop.drive.root_id' => 'ROOT', 'shop.drive.key_file' => $key]);
        \Illuminate\Support\Facades\Cache::put('gdrive_token', 'test-token', 600);
        $n = 0;
        \Illuminate\Support\Facades\Http::fake([
            'www.googleapis.com/*' => function ($request) use (&$n) {
                return \Illuminate\Support\Facades\Http::response($request->method() === 'POST' ? ['id' => 'folder-' . ++$n] : ['files' => []]);
            },
        ]);

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'parent_id' => Category::first()->id])->assertRedirect();
        $sub = Category::where('name_ru', 'Круглые')->first();
        $this->assertNotNull($sub->drive_folder_id);

        $p = Product::create(['category_id' => $sub->id, 'sku' => '11000', 'slug' => 'k1', 'name_uk' => 'К', 'name_ru' => 'К', 'price' => 1, 'stock' => 1, 'is_active' => true]);
        $this->assertNotNull(\App\Services\DriveSync::product($p));
        $this->assertNotNull($p->fresh()->drive_folder_id);

        @unlink($key);
    }

    public function test_admin_calculator_page_and_save(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.calculator.index'))->assertOk()->assertSee('Калькулятор цены');

        $m = \App\Models\CalcMaterial::first();
        $this->actingAs($admin)->put(route('admin.calculator.update'), [
            'calc_waste' => 80, 'calc_engr_rate' => 0.5, 'calc_overhead' => 10, 'calc_margin' => 40, 'calc_min_price' => 0, 'calc_round' => 5,
            'materials' => [['id' => $m->id, 'name' => 'Фанера', 'thickness' => 3, 'sheet_w' => 1525, 'sheet_h' => 1525, 'sheet_price' => 900, 'cut_rate' => 6, 'link' => '']],
        ])->assertRedirect();

        $this->assertSame('80', Setting::get('calc_waste'));
        $this->assertEquals(900, $m->fresh()->sheet_price);
        $this->assertNotNull($m->fresh()->price_date); // цена изменилась — дата проверки обновилась
    }

    public function test_round_product_size_text(): void
    {
        $cat = Category::create(['slug' => 'r', 'name_uk' => 'Р', 'name_ru' => 'Р', 'sort' => 1, 'sku_start' => 10000]);
        $p = Product::create(['category_id' => $cat->id, 'sku' => '10000', 'slug' => 'round', 'name_uk' => 'К', 'name_ru' => 'К', 'price' => 1, 'stock' => 1, 'is_active' => true, 'size_diameter' => 120, 'size_height' => 50]);
        app()->setLocale('ru');
        $this->assertSame('⌀ 120 мм, высота 50 мм', $p->size_text);
    }

    public function test_catalog_diameter_filter(): void
    {
        $cat = Category::create(['slug' => 'd', 'name_uk' => 'Д', 'name_ru' => 'Д', 'sort' => 1, 'sku_start' => 10000]);
        foreach ([[120, 'a'], [150, 'b']] as [$d, $slug]) {
            Product::create(['category_id' => $cat->id, 'sku' => (string) (20000 + $d), 'slug' => $slug, 'name_uk' => 'Коробка ' . $slug, 'name_ru' => 'Коробка ' . $slug, 'price' => 1, 'stock' => 1, 'is_active' => true, 'size_diameter' => $d]);
        }

        $this->get('/catalog')->assertOk()->assertSee('⌀ 120')->assertSee('⌀ 150');
        $this->get('/catalog?dia[]=120')->assertOk()->assertSee('Коробка a')->assertDontSee('Коробка b');
    }

    public function test_admin_bulk_price_change(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'b', 'name_uk' => 'Б', 'name_ru' => 'Б', 'sort' => 1, 'sku_start' => 10000]);
        $mk = fn ($sku, $price, $old = null) => Product::create(['category_id' => $cat->id, 'sku' => $sku, 'slug' => 'p' . $sku, 'name_uk' => 'Т', 'name_ru' => 'Т', 'price' => $price, 'old_price' => $old, 'stock' => 1, 'is_active' => true]);
        $a = $mk('1', 100);
        $b = $mk('2', 100, 110);
        $c = $mk('3', 100);

        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('Массовое изменение цены');

        // +20% двум выбранным: третий не трогаем; у товара со старой ценой 110 новая цена 120 — скидка снимается
        $this->actingAs($admin)->post(route('admin.products.bulk-price'), ['mode' => 'percent_up', 'value' => 20, 'round' => 1, 'ids' => [$a->id, $b->id]])->assertRedirect();
        $this->assertEquals(120, $a->fresh()->price);
        $this->assertEquals(120, $b->fresh()->price);
        $this->assertNull($b->fresh()->old_price);
        $this->assertEquals(100, $c->fresh()->price);

        // без выбранных товаров — ошибка, цены не меняются
        $this->actingAs($admin)->post(route('admin.products.bulk-price'), ['mode' => 'set', 'value' => 5, 'round' => 0])->assertSessionHasErrors('bulk');
        $this->assertEquals(100, $c->fresh()->price);

        // всем: установить цену 250
        $this->actingAs($admin)->post(route('admin.products.bulk-price'), ['mode' => 'set', 'value' => 250, 'round' => 0, 'scope_all' => 1])->assertRedirect();
        $this->assertEquals(250, $c->fresh()->price);
    }

    public function test_product_form_has_category_and_subcategory(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'gift', 'name_uk' => 'Коробки', 'name_ru' => 'Подарочные коробки', 'sort' => 1, 'sku_start' => 10000]);
        Category::create(['parent_id' => $top->id, 'slug' => 'round', 'name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'sku_start' => 11000]);

        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()->assertSee('Подкатегория')->assertSee('Круглые')->assertSee('Подарочные коробки');
    }

    public function test_product_creates_new_category_and_subcategory_inline(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $base = ['name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 50, 'stock' => 1, 'is_active' => 1];

        // Новая категория + новая подкатегория прямо из формы товара
        $this->actingAs($admin)->post(route('admin.products.store'), $base + [
            'category_top' => 'new', 'category_id' => 'new',
            'new_cat_uk' => 'Подарунки', 'new_cat_ru' => 'Подарки', 'new_sub_uk' => 'Малі', 'new_sub_ru' => 'Малые',
        ])->assertRedirect();

        $top = Category::where('name_ru', 'Подарки')->first();
        $sub = Category::where('name_ru', 'Малые')->first();
        $this->assertNotNull($top);
        $this->assertSame($top->id, $sub->parent_id);
        $product = Product::first();
        $this->assertSame($sub->id, $product->category_id);
        $this->assertStringStartsWith('11', $product->sku); // 10000-й блок категории, подкатегория — 11000…

        // Товар прямо в существующей категории
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['category_top' => $top->id, 'category_id' => 'self'])->assertRedirect();
        $this->assertSame($top->id, Product::latest('id')->first()->category_id);

        // Ошибка формы (нет названия новой категории) — ничего не создаётся
        $before = Category::count();
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['category_top' => 'new', 'category_id' => 'self'])->assertSessionHasErrors('new_cat_uk');
        $this->assertSame($before, Category::count());
    }
}
