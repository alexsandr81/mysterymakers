<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderPlaced;
use App\Models\Category;
use App\Models\Characteristic;
use App\Models\CharacteristicValue;
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

    protected function setUp(): void
    {
        parent::setUp();
        // Тесты не должны обращаться к настоящему Google Диску, даже если он настроен в .env
        config(['shop.drive.root_id' => null]);
    }

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

        foreach (['name', 'sku', 'category', 'sub', 'price', 'stock', 'active', 'created'] as $col) {
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
            'calc_waste' => 80, 'calc_engr_cm_rate' => 0.12, 'calc_filament_price' => 0.9, 'calc_overhead' => 10, 'calc_margin' => 40, 'calc_min_price' => 0, 'calc_round' => 5,
            'materials' => [['id' => $m->id, 'name' => 'Фанера', 'thickness' => 3, 'sheet_w' => 1525, 'sheet_h' => 1525, 'sheet_price' => 900, 'cut_rate' => 6, 'link' => '']],
        ])->assertRedirect();

        $this->assertSame('80', Setting::get('calc_waste'));
        $this->assertSame('0.9', Setting::get('calc_filament_price'));
        $this->assertSame('0.12', Setting::get('calc_engr_cm_rate'));
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

    public function test_admin_product_list_shows_category_and_subcategory_separately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'gift', 'name_uk' => 'Коробки', 'name_ru' => 'Подарочные коробки', 'sort' => 1, 'sku_start' => 10000]);
        $sub = Category::create(['parent_id' => $top->id, 'slug' => 'round', 'name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'sku_start' => 11000]);
        Product::create(['category_id' => $sub->id, 'slug' => 'p1', 'sku' => '11001', 'name_uk' => 'Т1', 'name_ru' => 'Т1', 'price' => 10, 'stock' => 1, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()->assertSee('Подкатегория')->assertSee('badge--top', false)->assertSee('badge--sub', false)
            ->assertSee('Подарочные коробки')->assertSee('Круглые');
    }

    public function test_moderator_has_full_access_except_users_journal_and_review_marks(): void
    {
        $mod = User::factory()->create(['is_moderator' => true]);
        $p = $this->product();

        foreach (['admin.dashboard', 'admin.products.index', 'admin.categories.index', 'admin.orders.index', 'admin.settings.edit', 'admin.calculator.index'] as $r) {
            $this->actingAs($mod)->get(route($r))->assertOk();
        }
        foreach (['admin.backups.index', 'admin.reviews.index', 'admin.novaposhta.edit'] as $r) {
            $this->assertNotSame(403, $this->actingAs($mod)->get(route($r))->status(), $r); // доступ есть (страница может просить настройки)
        }
        $this->actingAs($mod)->get(route('admin.products.index'))->assertOk()->assertSee('Массовое изменение цены');
        $this->actingAs($mod)->get(route('admin.products.create'))->assertOk()->assertSee('name="price"', false);

        // Пользователи и роли, журнал и «Проверено» — только у администратора
        $other = User::factory()->create();
        $this->actingAs($mod)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($mod)->get(route('admin.users.edit', $mod))->assertForbidden();
        $this->actingAs($mod)->put(route('admin.users.update', $mod), ['name' => 'x', 'email' => $mod->email, 'role' => 'admin'])->assertForbidden();
        $this->assertFalse($mod->fresh()->is_admin);
        $this->actingAs($mod)->delete(route('admin.users.destroy', $other))->assertForbidden();
        $this->actingAs($mod)->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs($mod)->post(route('admin.products.reviewed', $p))->assertForbidden();
        $this->actingAs($mod)->post(route('admin.products.bulk-reviewed'), ['ids' => [$p->id]])->assertForbidden();

        // Покупатель в админку не попадает
        $this->actingAs(User::factory()->create())->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_moderator_product_waits_for_review_and_actions_are_logged(): void
    {
        $mod = User::factory()->create(['is_moderator' => true, 'name' => 'Жена']);
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'c', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $base = ['name_uk' => 'Скринька', 'name_ru' => 'Шкатулка', 'stock' => 3, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];

        // Без цены можно: товар создаётся с пустой ценой и меткой «на проверку»
        $this->actingAs($mod)->post(route('admin.products.store'), $base)->assertRedirect();
        $p = Product::where('name_ru', 'Шкатулка')->first();
        $this->assertNotNull($p);
        $this->assertSame(0.0, (float) $p->price);
        $this->assertTrue($p->needs_review);
        $this->assertSame($mod->id, $p->modified_by);

        // На сайте виден сразу, но без цены и без покупки
        $this->withSession(['locale' => 'ru'])->get(route('product', $p))->assertOk()->assertSee('Цена уточняется')->assertDontSee('name="quantity"', false);
        $this->withSession(['locale' => 'ru'])->get('/catalog')->assertOk()->assertSee('Шкатулка')->assertSee('Цена уточняется');
        $this->post(route('cart.add', $p))->assertNotFound();

        // Модератор тоже может поставить цену — товар всё равно остаётся «на проверку»
        $this->actingAs($mod)->put(route('admin.products.update', $p), $base + ['price' => 300])->assertRedirect();
        $p->refresh();
        $this->assertSame(300.0, (float) $p->price);
        $this->assertTrue($p->needs_review);

        // Ошибка в форме в журнал не попадает
        $before = \App\Models\Activity::count();
        $this->actingAs($mod)->post(route('admin.products.store'), ['name_ru' => ''] + $base)->assertSessionHasErrors();
        $this->assertSame($before, \App\Models\Activity::count());

        // Журнал: добавление и изменение, с именем модератора и ссылкой на товар
        $this->assertSame(2, \App\Models\Activity::count());
        $this->actingAs($admin)->get(route('admin.activity.index'))->assertOk()
            ->assertSee('Жена')->assertSee('Добавлен товар')->assertSee('Изменён товар')->assertSee('Шкатулка')->assertSee(route('admin.products.edit', $p), false);

        // Счётчик «новое» у администратора обнуляется после просмотра журнала
        $this->assertSame(0, \App\Models\Activity::unseenCount($admin->fresh()));
        $this->travel(5)->seconds(); // следующее действие — уже после просмотра журнала
        $this->actingAs($mod)->put(route('admin.products.update', $p), $base + ['price' => 310])->assertRedirect();
        $this->assertSame(1, \App\Models\Activity::unseenCount($admin->fresh()));
        $poll = $this->actingAs($admin)->getJson(route('admin.orders.poll'))->assertOk()->json();
        $this->assertSame(1, $poll['act_unseen']);
        $this->assertStringContainsString('Жена: Изменён товар «Шкатулка', $poll['act_text']);

        // Действия администратора в журнал не пишутся
        $n = \App\Models\Activity::count();
        $this->actingAs($admin)->put(route('admin.products.update', $p), $base + ['price' => 450, 'reviewed' => 1])->assertSessionHasNoErrors();
        $this->assertSame($n, \App\Models\Activity::count());
        $this->assertFalse($p->fresh()->needs_review);
        $this->withSession(['locale' => 'ru'])->get(route('product', $p))->assertOk()->assertSee('name="quantity"', false);

        // Метка возвращается после правки модератора; ✔ и массовая отметка у администратора её снимают
        $this->actingAs($mod)->put(route('admin.products.update', $p), array_merge($base, ['name_ru' => 'Шкатулка 2', 'price' => 450]))->assertRedirect();
        $this->assertTrue($p->fresh()->needs_review);
        $this->actingAs($admin)->get(route('admin.products.index', ['review' => 1]))->assertOk()->assertSee('проверить')->assertSee('Шкатулка 2');
        $this->actingAs($admin)->post(route('admin.products.reviewed', $p))->assertRedirect();
        $this->assertFalse($p->fresh()->needs_review);
        Product::whereKey($p->id)->update(['needs_review' => true]); // напрямую в базу: $p в памяти устарел, и save() ничего бы не записал
        $this->actingAs($admin)->post(route('admin.products.bulk-reviewed'), ['ids' => [$p->id]])->assertRedirect();
        $this->assertFalse($p->fresh()->needs_review);

        // Администратор без галочки «Проверено» оставляет метку
        Product::whereKey($p->id)->update(['needs_review' => true]); // напрямую в базу: $p в памяти устарел, и save() ничего бы не записал
        $this->actingAs($admin)->put(route('admin.products.update', $p), $base + ['price' => 450])->assertSessionHasNoErrors();
        $this->assertTrue($p->fresh()->needs_review);

        // Админ без цены сохранить товар не может
        $this->actingAs($admin)->put(route('admin.products.update', $p), $base)->assertSessionHasErrors('price');
    }

    public function test_admin_chooses_which_sections_moderator_sees(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mod = User::factory()->create(['is_moderator' => true]);
        $form = ['name' => $mod->name, 'email' => $mod->email, 'role' => 'moderator', 'sections_present' => '1'];

        // По умолчанию (список не задавали) модератору открыто всё
        $this->assertNull($mod->admin_sections);
        $this->actingAs($mod)->get(route('admin.calculator.index'))->assertOk();

        // Администратор оставляет только товары и категории
        $this->actingAs($admin)->put(route('admin.users.update', $mod), $form + ['sections' => ['products', 'categories']])->assertSessionHasNoErrors();
        $this->assertSame(['products', 'categories'], $mod->fresh()->admin_sections);

        $this->actingAs($mod->fresh())->get(route('admin.products.index'))->assertOk();
        $this->actingAs($mod->fresh())->get(route('admin.categories.index'))->assertOk();
        foreach (['admin.orders.index', 'admin.reviews.index', 'admin.calculator.index', 'admin.settings.edit', 'admin.backups.index', 'admin.users.index'] as $name) {
            $this->actingAs($mod->fresh())->get(route($name))->assertForbidden()->assertSee('Нет доступа к этому разделу');
        }
        // Дашборд открыт всегда, закрытых разделов в меню нет
        $this->actingAs($mod->fresh())->get(route('admin.dashboard'))->assertOk()->assertSee('Товары')->assertDontSee('Калькулятор')->assertDontSee('Заказы');

        // Неизвестный раздел отклоняется; форма без списка ничего не меняет
        $this->actingAs($admin)->put(route('admin.users.update', $mod), $form + ['sections' => ['hacks']])->assertSessionHasErrors('sections.0');
        $this->actingAs($admin)->put(route('admin.users.update', $mod), ['name' => $mod->name, 'email' => $mod->email, 'role' => 'moderator'])->assertRedirect();
        $this->assertSame(['products', 'categories'], $mod->fresh()->admin_sections);

        // Форма пользователя показывает выбор разделов
        $this->actingAs($admin)->get(route('admin.users.edit', $mod))->assertOk()->assertSee('Какие разделы видит модератор');

        // Роль не модератор — список сбрасывается
        $this->actingAs($admin)->put(route('admin.users.update', $mod), ['name' => $mod->name, 'email' => $mod->email, 'role' => 'customer'])->assertRedirect();
        $this->assertNull($mod->fresh()->admin_sections);
    }

    public function test_admin_can_assign_roles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $u = User::factory()->create();
        $form = ['name' => $u->name, 'email' => $u->email];

        $this->actingAs($admin)->put(route('admin.users.update', $u), $form + ['role' => 'moderator'])->assertRedirect();
        $this->assertSame('moderator', $u->fresh()->role());

        $this->actingAs($admin)->put(route('admin.users.update', $u), $form + ['role' => 'admin'])->assertRedirect();
        $this->assertSame('admin', $u->fresh()->role());
        $this->assertFalse($u->fresh()->is_moderator);

        // Нельзя оставить сайт без администратора
        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'moderator'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->put(route('admin.users.update', $u), $form + ['role' => 'boss'])->assertSessionHasErrors('role');
    }

    public function test_product_form_creates_new_characteristic_value_and_characteristic_inline(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'c', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $color = \App\Models\Characteristic::create(['name_uk' => 'Колір', 'name_ru' => 'Цвет', 'is_filterable' => true, 'sort' => 1]);
        $white = $color->values()->create(['value_uk' => 'Білий', 'value_ru' => 'Белый', 'sort' => 1]);
        $base = ['name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 50, 'stock' => 1, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];

        // Новое значение в существующей характеристике
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['char' => [$color->id => 'new'], 'new_val' => [$color->id => ['uk' => 'Рожевий', 'ru' => 'Розовый']]])->assertSessionHasNoErrors();
        $p = Product::latest('id')->first();
        $pink = $color->values()->where('value_ru', 'Розовый')->first();
        $this->assertNotNull($pink);
        $this->assertSame([$pink->id], $p->characteristicValues()->pluck('characteristic_values.id')->all());

        // Такое значение уже есть (регистр не важен) — дубль не создаётся
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['char' => [$color->id => 'new'], 'new_val' => [$color->id => ['uk' => 'білий', 'ru' => 'БЕЛЫЙ']]])->assertSessionHasNoErrors();
        $this->assertSame(2, $color->values()->count());
        $this->assertSame([$white->id], Product::latest('id')->first()->characteristicValues()->pluck('characteristic_values.id')->all());

        // Не заполнено название нового значения — ошибка, ничего не создаётся
        $products = Product::count();
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['char' => [$color->id => 'new'], 'new_val' => [$color->id => ['uk' => 'Синій', 'ru' => '']]])->assertSessionHasErrors("new_val.{$color->id}.ru");
        $this->assertSame($products, Product::count());

        // Новая характеристика целиком (название + первое значение)
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['new_char_name_uk' => 'Розмір', 'new_char_name_ru' => 'Размер', 'new_char_val_uk' => 'Великий', 'new_char_val_ru' => 'Большой'])->assertSessionHasNoErrors();
        $size = \App\Models\Characteristic::where('name_ru', 'Размер')->first();
        $this->assertNotNull($size);
        $this->assertTrue($size->is_filterable);
        $this->assertSame(['Большой'], $size->values->pluck('value_ru')->all());
        $this->assertSame(1, Product::latest('id')->first()->characteristicValues()->count());

        // Название без значения — ошибка
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['new_char_name_uk' => 'Вага', 'new_char_name_ru' => 'Вес'])->assertSessionHasErrors(['new_char_val_uk', 'new_char_val_ru']);

        // Форма показывает «＋ Новое значение» и «＋ Новая характеристика»
        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Новое значение')->assertSee('Новая характеристика');
    }

    public function test_logo_seal_is_shown_in_header_login_and_favicon(): void
    {
        $this->get('/')->assertOk()->assertSee('class="logo-seal"', false)->assertDontSee('aria-label="RU"', false)->assertSee('favicon.svg', false)->assertSee('Cinzel+Decorative', false);
        $this->get(route('login'))->assertOk()->assertSee('МАЙСТЕРНЯ З ФАНЕРИ', false)->assertSee('MYSTERY MAKERS', false);
        $this->assertFileExists(public_path('favicon.svg'));
    }

    public function test_category_sku_change_shifts_subcategories_and_products(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 't', 'name_uk' => 'Коробки', 'name_ru' => 'Коробки', 'sort' => 1, 'sku_start' => 10000]);
        $sub = Category::create(['parent_id' => $top->id, 'slug' => 's', 'name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'sku_start' => 11000]);
        $other = Category::create(['slug' => 'o', 'name_uk' => 'Іграшки', 'name_ru' => 'Игрушки', 'sort' => 2, 'sku_start' => 20000]);
        $mk = fn ($cat, $sku, $slug) => Product::create(['category_id' => $cat->id, 'sku' => $sku, 'slug' => $slug, 'name_uk' => $slug, 'name_ru' => $slug, 'price' => 1, 'stock' => 1, 'is_active' => true]);
        $a = $mk($top, '10000', 'a');
        $b = $mk($sub, '11000', 'b');
        $c = $mk($sub, '11001', 'c');
        $manual = $mk($top, 'ABC-1', 'manual');
        $row = fn ($cat, $start) => ['name_uk' => $cat->name_uk, 'name_ru' => $cat->name_ru, 'sort' => $cat->sort, 'sku_start' => $start] + ($cat->parent_id ? ['parent_id' => $cat->parent_id] : []);

        // Новый блок пересекается с соседней категорией — ничего не меняется
        $this->actingAs($admin)->put(route('admin.categories.update', $top), $row($top, 15000))->assertSessionHasErrors('sku_start');
        $this->assertSame(10000, (int) $top->fresh()->sku_start);
        $this->assertSame('11000', $b->fresh()->sku);

        // Сдвиг вперёд на 20000 (блок 30000–39999 свободен)
        $this->actingAs($admin)->put(route('admin.categories.update', $top), $row($top, 30000))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(30000, (int) $top->fresh()->sku_start);
        $this->assertSame(31000, (int) $sub->fresh()->sku_start);
        $this->assertSame(20000, (int) $other->fresh()->sku_start);
        $this->assertSame('30000', $a->fresh()->sku);
        $this->assertSame('31000', $b->fresh()->sku);
        $this->assertSame('31001', $c->fresh()->sku);
        $this->assertSame('ABC-1', $manual->fresh()->sku); // нечисловые артикулы не трогаем

        // И обратно, с пересечением старого и нового диапазонов (30000 → 30500)
        $this->actingAs($admin)->put(route('admin.categories.update', $top), $row($top, 30500))->assertSessionHasNoErrors();
        $this->assertSame('30500', $a->fresh()->sku);
        $this->assertSame('31500', $b->fresh()->sku);
        $this->assertSame(31500, (int) $sub->fresh()->sku_start);

        // Если новый диапазон занят чужим артикулом — отказ
        $mk($other, '50100', 'foreign');
        $this->actingAs($admin)->put(route('admin.categories.update', $top), $row($top, 50000))->assertSessionHasErrors('sku_start');
        $this->assertSame('30500', $a->fresh()->sku);

        // Сохранение без изменения числа артикулы не двигает
        $this->actingAs($admin)->put(route('admin.categories.update', $top), $row($top->fresh(), 30500))->assertSessionHasNoErrors();
        $this->assertSame('30500', $a->fresh()->sku);
    }

    public function test_backup_restore_and_upload_are_admin_only_and_validated(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mod = User::factory()->create(['is_moderator' => true]);

        $dir = storage_path('backups');
        @mkdir($dir, 0775, true);
        $name = 'shop-2000-01-01_000000.zip';
        file_put_contents($dir . '/' . $name, 'not a zip');

        // Модератор не может ни восстановить, ни загрузить копию
        $this->actingAs($mod)->post(route('admin.backups.restore', $name))->assertForbidden();
        $this->actingAs($mod)->post(route('admin.backups.upload'))->assertForbidden();

        // Администратор: испорченный архив отклоняется, данные не трогаются
        $this->actingAs($admin)->post(route('admin.backups.restore', $name))->assertRedirect()->assertSessionHas('error');
        $this->assertTrue(User::whereKey($admin->id)->exists());
        // Чужое имя файла не принимается
        $this->actingAs($admin)->post(route('admin.backups.restore', '..evil.zip'))->assertNotFound();
        // Список копий показывает кнопки администратору
        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee('Восстановить')->assertSee('Загрузить');

        // Запись о последнем восстановлении показывается на странице копий (и подтверждение после перехода с ?restored=1)
        $state = storage_path('app/last-restore.json');
        file_put_contents($state, json_encode(['file' => $name, 'backup_time' => time() - 3600, 'at' => time(), 'with_files' => true, 'files' => 7, 'safety' => 'shop-2000-01-02_000000.zip', 'note' => ''], JSON_UNESCAPED_UNICODE));
        $this->actingAs($admin)->get(route('admin.backups.index', ['restored' => 1]))->assertOk()->assertSee('Резервная копия восстановлена')->assertSee('shop-2000-01-02_000000.zip')->assertSee('Фото из копии: 7');
        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee('Последнее восстановление')->assertDontSee('Резервная копия восстановлена');
        @unlink($state);

        @unlink($dir . '/' . $name);
    }

    public function test_product_sku_follows_category_when_moved(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $a = Category::create(['slug' => 'a', 'name_uk' => 'А', 'name_ru' => 'А', 'sort' => 1, 'sku_start' => 10000]);
        $b = Category::create(['slug' => 'b', 'name_uk' => 'Б', 'name_ru' => 'Б', 'sort' => 2, 'sku_start' => 20000]);
        $sub = Category::create(['parent_id' => $b->id, 'slug' => 'bs', 'name_uk' => 'БП', 'name_ru' => 'БП', 'sort' => 1, 'sku_start' => 21000]);
        $p = Product::create(['category_id' => $a->id, 'sku' => '10000', 'slug' => 'm', 'name_uk' => 'М', 'name_ru' => 'М', 'price' => 5, 'stock' => 1, 'is_active' => true]);
        $form = fn ($sku, $top, $subId) => ['name_uk' => 'М', 'name_ru' => 'М', 'price' => 5, 'stock' => 1, 'is_active' => 1, 'sku' => $sku, 'category_top' => (string) $top, 'category_id' => $subId];

        // Переносим в подкатегорию, артикул в форме не трогали — получает номер из блока подкатегории
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('10000', $b->id, (string) $sub->id))->assertSessionHasNoErrors();
        $this->assertSame($sub->id, $p->fresh()->category_id);
        $this->assertSame('21000', $p->fresh()->sku);

        // Обратно в категорию верхнего уровня
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('21000', $b->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('20000', $p->fresh()->sku);

        // Без смены категории артикул остаётся
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('20000', $b->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('20000', $p->fresh()->sku);

        // Артикул стёрли — берётся номер по текущей категории; если уже из её блока, остаётся прежний
        $p->update(['sku' => '11206']);
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('', $b->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('20000', $p->fresh()->sku);
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('', $b->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('20000', $p->fresh()->sku);

        // Артикул ввели вручную — уважаем
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('MY-7', $a->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('MY-7', $p->fresh()->sku);
        // Нечисловой (ручной) артикул при переносе не заменяется
        $this->actingAs($admin)->put(route('admin.products.update', $p), $form('MY-7', $b->id, 'self'))->assertSessionHasNoErrors();
        $this->assertSame('MY-7', $p->fresh()->sku);
    }

    public function test_categories_tree_has_collapsible_subcategories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'k', 'name_uk' => 'Коробки', 'name_ru' => 'Коробки', 'sort' => 1, 'sku_start' => 10000]);
        $sub = Category::create(['parent_id' => $top->id, 'slug' => 'kr', 'name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'sku_start' => 11000]);
        Category::create(['slug' => 'e', 'name_uk' => 'Пустая', 'name_ru' => 'Пустая', 'sort' => 2, 'sku_start' => 20000]);

        // Дерево: у категории с подкатегориями есть галочка, а строка подкатегории привязана к родителю
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()
            ->assertSee('data-id="' . $top->id . '"', false)
            ->assertSee('data-parent="' . $top->id . '"', false)
            ->assertSee('Развернуть все');

        // Плоский список при сортировке — без галочек
        $this->actingAs($admin)->get(route('admin.categories.index', ['sort' => 'name']))->assertOk()
            ->assertDontSee('data-parent=', false)->assertDontSee('Развернуть все');

        // После создания подкатегории её родитель раскрывается
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name_uk' => 'Нова', 'name_ru' => 'Новая', 'sort' => 2, 'parent_id' => $top->id])
            ->assertRedirect(route('admin.categories.index'))->assertSessionHas('open_category', $top->id);
    }

    public function test_admin_products_grouped_by_category_and_flat_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'k', 'name_uk' => 'Коробки', 'name_ru' => 'Коробки', 'sort' => 1, 'sku_start' => 10000]);
        $sub = Category::create(['parent_id' => $top->id, 'slug' => 'kr', 'name_uk' => 'Круглі', 'name_ru' => 'Круглые', 'sort' => 1, 'sku_start' => 11000]);
        Category::create(['slug' => 'e', 'name_uk' => 'Пустая', 'name_ru' => 'Пустая', 'sort' => 2, 'sku_start' => 20000]);
        $mk = fn ($cat, $sku, $name) => Product::create(['category_id' => $cat->id, 'sku' => $sku, 'slug' => 's' . $sku, 'name_uk' => $name, 'name_ru' => $name, 'price' => 10, 'stock' => 1, 'is_active' => true]);
        $mk($top, '10000', 'Прямая коробка');
        $mk($sub, '11000', 'Круглая коробка');

        // По умолчанию — группы: заголовок категории, подкатегории, пустая категория не показывается
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()
            ->assertSee('data-ghead="' . $top->id . '"', false)
            ->assertSee('data-shead="' . $sub->id . '"', false)
            ->assertSee('Прямая коробка')->assertSee('Круглая коробка')
            ->assertDontSee('Пустая')->assertSee('Списком');

        // Общее количество товаров
        $this->actingAs($admin)->get(route('admin.products.index'))->assertSee('Всего товаров: <strong>2</strong>', false)->assertSee('активных: <strong>2</strong>', false);
        $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'Круглая']))->assertSee('Всего товаров: <strong>2</strong>', false)->assertSee('показано по фильтру: <strong>1</strong>', false);

        // Поиск, сортировка и явный список — плоская таблица с пагинацией
        foreach ([['q' => 'Круглая'], ['sort' => 'name'], ['view' => 'list']] as $params) {
            $this->actingAs($admin)->get(route('admin.products.index', $params))->assertOk()->assertDontSee('data-ghead=', false);
        }
        $this->actingAs($admin)->get(route('admin.products.index', ['view' => 'list']))->assertSee('По категориям');
    }

    public function test_admin_can_clear_activity_journal(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mod = User::factory()->create(['is_moderator' => true, 'name' => 'Жена']);
        $mod2 = User::factory()->create(['is_moderator' => true, 'name' => 'Помощник']);
        $row = fn ($u, $label) => \App\Models\Activity::create(['user_id' => $u->id, 'user_name' => $u->name, 'label' => $label, 'subject' => 'Т', 'url' => null]);
        $row($mod, 'Добавлен товар');
        $row($mod, 'Изменён товар');
        $row($mod2, 'Удалён товар');

        $this->actingAs($admin)->get(route('admin.activity.index'))->assertOk()->assertSee('Очистить журнал');

        // Модератор очистить журнал не может
        $this->actingAs($mod)->delete(route('admin.activity.clear'))->assertForbidden();
        $this->assertSame(3, \App\Models\Activity::count());

        // С фильтром — только записи выбранного модератора
        $this->actingAs($admin)->delete(route('admin.activity.clear'), ['user' => $mod->id])->assertRedirect()->assertSessionHas('status');
        $this->assertSame(1, \App\Models\Activity::count());
        $this->assertSame($mod2->id, \App\Models\Activity::first()->user_id);

        // Без фильтра — весь журнал
        $this->actingAs($admin)->delete(route('admin.activity.clear'))->assertRedirect();
        $this->assertSame(0, \App\Models\Activity::count());
        $this->actingAs($admin)->get(route('admin.activity.index'))->assertOk()->assertDontSee('Очистить журнал');
    }

    public function test_activity_links_are_relative_and_products_open_on_site(): void
    {
        $mod = User::factory()->create(['is_moderator' => true, 'name' => 'Жена']);
        $cat = Category::create(['slug' => 'c', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $base = ['name_uk' => 'Скринька', 'name_ru' => 'Шкатулка', 'stock' => 3, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];

        $this->actingAs($mod)->post(route('admin.products.store'), $base)->assertRedirect();
        $p = Product::where('name_ru', 'Шкатулка')->first();
        $this->actingAs($mod)->put(route('admin.products.update', $p), array_merge($base, ['price' => 100]))->assertRedirect();

        // В журнале — путь без домена, ведущий на страницу товара на сайте
        foreach (\App\Models\Activity::where('subject', 'Шкатулка')->get() as $a) {
            $this->assertSame(route('product', $p, false), parse_url($a->url, PHP_URL_PATH));
            $this->assertStringNotContainsString('http', (string) $a->url);
        }

        // Старая запись с полным адресом редактирования (на другом домене) тоже открывается на текущем сайте и ведёт на товар
        $old = \App\Models\Activity::create(['user_id' => $mod->id, 'user_name' => 'Жена', 'label' => 'Изменён товар', 'subject' => 'Шкатулка', 'url' => 'https://old-domain.ngrok-free.dev/admin/products/' . $p->id . '/edit']);
        $this->assertSame(route('product', $p, false), $old->link);
        $old2 = \App\Models\Activity::create(['user_id' => $mod->id, 'user_name' => 'Жена', 'label' => 'Изменена категория', 'subject' => 'К', 'url' => 'https://old-domain.ngrok-free.dev/admin/categories/' . $cat->id . '/edit']);
        $this->assertSame('/admin/categories/' . $cat->id . '/edit', $old2->link);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.activity.index'))->assertOk()->assertSee('href="' . route('product', $p, false) . '"', false)->assertDontSee('old-domain');
        // Страница товара открывается
        $this->actingAs($admin)->get(route('product', $p))->assertOk();
    }

    public function test_group_folder_links_to_drive(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'dk', 'name_uk' => 'К', 'name_ru' => 'Папочная', 'sort' => 1, 'sku_start' => 10000]);
        Category::whereKey($cat->id)->update(['drive_folder_id' => 'FOLDER123']);
        Product::create(['name_uk' => 'Т', 'name_ru' => 'Т', 'slug' => 't', 'price' => 10, 'stock' => 1, 'is_active' => 1, 'category_id' => $cat->id]);
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('FOLDER123');
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('FOLDER123');
    }

    public function test_admin_save_keeps_review_mark_unless_confirmed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'rv', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $p = Product::create(['category_id' => $cat->id, 'sku' => '10001', 'slug' => 'rv1', 'name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 5, 'stock' => 1, 'is_active' => true]);
        Product::whereKey($p->id)->update(['needs_review' => true]);
        $base = ['name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 5, 'stock' => 1, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];

        $this->actingAs($admin)->get(route('admin.products.edit', $p))->assertOk()->assertDontSee('name="reviewed" value="1" checked', false);
        $this->actingAs($admin)->put(route('admin.products.update', $p), $base)->assertSessionHasNoErrors();
        $this->assertTrue((bool) Product::find($p->id)->needs_review);

        $this->actingAs($admin)->put(route('admin.products.update', $p), $base + ['reviewed' => 1])->assertSessionHasNoErrors();
        $this->assertFalse((bool) Product::find($p->id)->needs_review);
    }

    public function test_admin_can_send_product_to_review(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mod = User::factory()->create(['is_moderator' => true]);
        $cat = Category::create(['slug' => 'tr', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $p = Product::create(['category_id' => $cat->id, 'sku' => '10001', 'slug' => 'tr1', 'name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 5, 'stock' => 1, 'is_active' => true]);

        $this->actingAs($mod)->post(route('admin.products.to-review', $p))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.products.to-review', $p))->assertRedirect();
        $this->assertTrue((bool) Product::find($p->id)->needs_review);

        Product::whereKey($p->id)->update(['needs_review' => false]);
        $base = ['name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 5, 'stock' => 1, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];
        $this->actingAs($admin)->put(route('admin.products.update', $p), $base + ['to_review' => 1])->assertSessionHasNoErrors();
        $this->assertTrue((bool) Product::find($p->id)->needs_review);
    }

    public function test_grouped_products_are_indented_by_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $top = Category::create(['slug' => 'ih', 'name_uk' => 'К', 'name_ru' => 'Верх', 'sort' => 1, 'sku_start' => 10000]);
        $sub = Category::create(['parent_id' => $top->id, 'slug' => 'ih-s', 'name_uk' => 'П', 'name_ru' => 'Под', 'sort' => 1]);
        Product::create(['category_id' => $top->id, 'sku' => '10001', 'slug' => 'ih1', 'name_uk' => 'А', 'name_ru' => 'А', 'price' => 1, 'stock' => 1, 'is_active' => true]);
        Product::create(['category_id' => $sub->id, 'sku' => '11001', 'slug' => 'ih2', 'name_uk' => 'Б', 'name_ru' => 'Б', 'price' => 1, 'stock' => 1, 'is_active' => true]);
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('row-indent--1', false)->assertSee('row-indent--2', false);
    }

    public function test_bulk_characteristics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mod = User::factory()->create(['is_moderator' => true]);
        $cat = Category::create(['slug' => 'bc', 'name_uk' => 'К', 'name_ru' => 'К', 'sort' => 1, 'sku_start' => 10000]);
        $mk = fn ($n) => Product::create(['category_id' => $cat->id, 'sku' => (string) (10000 + $n), 'slug' => 'bc' . $n, 'name_uk' => 'Т' . $n, 'name_ru' => 'Т' . $n, 'price' => 5, 'stock' => 1, 'is_active' => true]);
        [$p1, $p2, $p3] = [$mk(1), $mk(2), $mk(3)];
        $mat = Characteristic::create(['name_uk' => 'Матеріал', 'name_ru' => 'Материал', 'sort' => 1]);
        $col = Characteristic::create(['name_uk' => 'Колір', 'name_ru' => 'Цвет', 'sort' => 2]);
        $fan = CharacteristicValue::create(['characteristic_id' => $mat->id, 'value_uk' => 'Фанера', 'value_ru' => 'Фанера', 'sort' => 1]);
        $mdf = CharacteristicValue::create(['characteristic_id' => $mat->id, 'value_uk' => 'МДФ', 'value_ru' => 'МДФ', 'sort' => 2]);
        $red = CharacteristicValue::create(['characteristic_id' => $col->id, 'value_uk' => 'Червоний', 'value_ru' => 'Красный', 'sort' => 1]);
        $p1->characteristicValues()->sync([$fan->id, $red->id]);
        $p2->characteristicValues()->sync([$mdf->id]);
        $vals = fn ($p) => $p->characteristicValues()->pluck('characteristic_values.id')->sort()->values()->all();

        // Заменить материал у двух товаров на МДФ: цвет не трогаем
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'replace', 'ch_value' => $mdf->id, 'ids' => [$p1->id, $p3->id]])->assertSessionHasNoErrors();
        $this->assertSame([$mdf->id, $red->id], $vals($p1));
        $this->assertSame([$mdf->id], $vals($p3));
        $this->assertSame([$mdf->id], $vals($p2));
        $this->assertFalse((bool) Product::find($p1->id)->needs_review);

        // Добавить фанеру (материалов станет два)
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'add', 'ch_value' => $fan->id, 'ids' => [$p3->id]]);
        $this->assertSame([$fan->id, $mdf->id], $vals($p3));

        // Новое значение создаётся и ставится
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $col->id, 'ch_mode' => 'replace', 'ch_new_uk' => 'Синій', 'ch_new_ru' => 'Синий', 'ids' => [$p1->id]]);
        $blue = CharacteristicValue::where('value_ru', 'Синий')->first();
        $this->assertNotNull($blue);
        $this->assertSame($col->id, $blue->characteristic_id);
        $this->assertContains($blue->id, $vals($p1));
        $this->assertNotContains($red->id, $vals($p1));

        // Чужое значение (другой характеристики) отклоняется
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'add', 'ch_value' => $red->id, 'ids' => [$p2->id]])->assertSessionHasErrors('bulk_ch');

        // Убрать без значения — все значения материала у всех товаров списка
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'remove', 'ch_all' => 1]);
        foreach ([$p1, $p2, $p3] as $p) {
            $this->assertSame([], array_values(array_intersect($vals($p), [$fan->id, $mdf->id])));
        }
        $this->assertContains($blue->id, $vals($p1)); // цвет остался

        // Без выбранных товаров — ошибка
        $this->actingAs($admin)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'replace', 'ch_value' => $fan->id])->assertSessionHasErrors('bulk_ch');

        // Модератор может, но товары уходят на проверку
        $this->actingAs($mod)->post(route('admin.products.bulk-characteristics'), ['ch_id' => $mat->id, 'ch_mode' => 'replace', 'ch_value' => $fan->id, 'ids' => [$p2->id]])->assertSessionHasNoErrors();
        $this->assertTrue((bool) Product::find($p2->id)->needs_review);
        $this->assertFalse((bool) Product::find($p3->id)->needs_review);

        // Панель есть на странице товаров
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('Массовое изменение характеристик');
    }

    public function test_site_fonts_setting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = ['site_name' => 'Mystery Makers', 'phone' => '+380501234567'];

        // По умолчанию — системный шрифт: выбранные шрифты сайта с Google Fonts не подгружаются (кроме шрифта логотипа)
        $this->get('/')->assertOk()->assertDontSee('family=Nunito', false)->assertDontSee('Playfair+Display', false);

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Шрифты')->assertSee('Playfair Display')->assertSee('Literata')->assertSee('Source Serif 4');

        $this->actingAs($admin)->put(route('admin.settings.update'), $form + ['font_body' => 'nunito', 'font_heading' => 'playfair', 'font_logo' => 'same'])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()
            ->assertSee('fonts.googleapis.com/css2?family=Nunito', false)
            ->assertSee('Playfair+Display', false)
            ->assertSee('--font-logo:"Playfair Display"', false);

        // Неизвестный шрифт отклоняется
        $this->actingAs($admin)->put(route('admin.settings.update'), $form + ['font_body' => 'evil; x', 'font_heading' => 'same', 'font_logo' => 'same'])->assertSessionHasErrors('font_body');
    }

    public function test_admin_tables_have_column_picker(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->product();

        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('data-tools="products"', false)->assertSee('⚙ Столбцы');
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('data-tools="categories"', false)->assertSee('⚙ Столбцы');
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
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['category_top' => (string) $top->id, 'category_id' => 'self'])->assertRedirect();
        $this->assertSame($top->id, Product::latest('id')->first()->category_id);

        // Ошибка формы (нет названия новой категории) — ничего не создаётся
        $before = Category::count();
        $this->actingAs($admin)->post(route('admin.products.store'), $base + ['category_top' => 'new', 'category_id' => 'self'])->assertSessionHasErrors('new_cat_uk');
        $this->assertSame($before, Category::count());
    }

    public function test_holidays_assigned_in_product_and_filtered_in_catalog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cat = Category::create(['slug' => 'h', 'name_uk' => 'Х', 'name_ru' => 'Х', 'sort' => 1, 'sku_start' => 10000]);
        $ny = \App\Models\Holiday::where('name_ru', 'Новый год')->first();
        $base = ['name_uk' => 'Т', 'name_ru' => 'Т', 'price' => 50, 'stock' => 1, 'is_active' => 1, 'category_top' => (string) $cat->id, 'category_id' => 'self'];

        $this->actingAs($admin)->get(route('admin.holidays.index'))->assertOk()->assertSee('Новый год');
        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Праздники и поводы');

        // Один товар: Новый год + новый праздник прямо из формы. Второй: без праздников.
        $this->actingAs($admin)->post(route('admin.products.store'), ['name_uk' => 'Ялинка', 'name_ru' => 'Ёлка'] + $base + ['holidays' => [$ny->id], 'new_holiday_uk' => 'Хелловін', 'new_holiday_ru' => 'Хэллоуин'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.products.store'), ['name_uk' => 'Шкатулка', 'name_ru' => 'Шкатулка'] + $base)->assertRedirect();

        $elka = Product::where('name_ru', 'Ёлка')->first();
        $this->assertSame(2, $elka->holidays()->count());
        $this->assertTrue(\App\Models\Holiday::where('name_ru', 'Хэллоуин')->exists());

        // В фильтре видны только праздники, у которых есть товары; выбор «Новый год» оставляет один товар
        $this->withSession(['locale' => 'ru'])->get('/catalog')->assertOk()->assertSee('Праздник / повод')->assertSee('Хэллоуин')->assertDontSee('Пасха');
        $this->withSession(['locale' => 'ru'])->get('/catalog?hol[]=' . $ny->id)->assertOk()->assertSee('Ёлка')->assertDontSee('Шкатулка');
    }
}
