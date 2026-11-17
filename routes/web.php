<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale');

    // Каталог и поиск
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalog/{category:slug}', [CatalogController::class, 'index'])->name('catalog.category');
    Route::get('/product/{product:slug}', [CatalogController::class, 'show'])->name('product');

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
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/account', [AccountController::class, 'index'])->name('account');
        Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
    });

    // Админка: только для пользователей с is_admin = 1
    Route::prefix('admin')->name('admin.')->middleware(['auth', EnsureAdmin::class])->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    });
});
