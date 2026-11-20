<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NovaPoshtaController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

// За туннелем (ngrok и т. п.) сайт открывается по https. Если APP_URL начинается с https://,
// все ссылки, стили и картинки строятся с https, иначе браузер блокирует их как «смешанный контент».
// Локальные заходы (127.0.0.1 / localhost) остаются по http.
if (str_starts_with((string) config('app.url'), 'https://')
    && !in_array(request()->getHost(), ['127.0.0.1', 'localhost', '::1'], true)) {
    URL::forceScheme('https');
}

Route::middleware(SetLocale::class)->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale');

    // Каталог и поиск
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalog/{category:slug}', [CatalogController::class, 'index'])->name('catalog.category');
    Route::get('/product/{product:slug}', [CatalogController::class, 'show'])->name('product');

    // Статические страницы («Доставка», «Контакты», «О нас»...)
    Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('page');

    // Подсказки Новой Почты (форма заказа)
    Route::get('/np/cities', [NovaPoshtaController::class, 'cities'])->middleware('throttle:60,1')->name('np.cities');
    Route::get('/np/warehouses', [NovaPoshtaController::class, 'warehouses'])->middleware('throttle:90,1')->name('np.warehouses');

    // Корзина
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{productId}', [CartController::class, 'remove'])->name('cart.remove');

    // Оформление заказа (доступно и гостям)
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/done', [CheckoutController::class, 'done'])->name('checkout.done');

    // Вход / регистрация
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
        Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');

        // Восстановление пароля
        Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/account', [AccountController::class, 'index'])->name('account');
        Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
        Route::patch('/account/password', [AccountController::class, 'password'])->name('account.password');
        Route::post('/product/{product:slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');
        Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    });

    // Админка: только для пользователей с is_admin = 1
    Route::prefix('admin')->name('admin.')->middleware(['auth', EnsureAdmin::class])->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::get('/backups', [Admin\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [Admin\BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{file}', [Admin\BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{file}', [Admin\BackupController::class, 'destroy'])->name('backups.destroy');
        Route::get('/novaposhta', [Admin\NovaPoshtaController::class, 'edit'])->name('novaposhta.edit');
        Route::put('/novaposhta', [Admin\NovaPoshtaController::class, 'update'])->name('novaposhta.update');
        Route::post('/orders/{order}/ttn', [Admin\OrderTtnController::class, 'store'])->name('orders.ttn.store');
        Route::post('/orders/{order}/ttn/refresh', [Admin\OrderTtnController::class, 'refresh'])->name('orders.ttn.refresh');
        Route::get('/orders/{order}/ttn/print', [Admin\OrderTtnController::class, 'print'])->name('orders.ttn.print');
        Route::delete('/orders/{order}/ttn', [Admin\OrderTtnController::class, 'destroy'])->name('orders.ttn.destroy');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}/status', [Admin\ReviewController::class, 'updateStatus'])->name('reviews.status');
        Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::resource('users', Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::resource('banners', Admin\BannerController::class)->except('show');
        Route::resource('characteristics', Admin\CharacteristicController::class)->except('show');
        Route::get('/orders/poll', [Admin\OrderController::class, 'poll'])->name('orders.poll');
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{order}', [Admin\OrderController::class, 'destroy'])->name('orders.destroy');
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    });
});
