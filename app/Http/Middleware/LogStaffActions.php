<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Characteristic;
use App\Models\Holiday;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Записывает в журнал каждое успешное изменение, сделанное модератором (любой не-GET запрос в админке).
 * Администратор видит журнал в «Действия модератора», счётчик в меню и всплывающее уведомление.
 */
class LogStaffActions
{
    /** имя маршрута (без «admin.») => что показать в журнале */
    private const LABELS = [
        'products.store' => 'Добавлен товар', 'products.update' => 'Изменён товар', 'products.destroy' => 'Удалён товар',
        'products.bulk-price' => 'Массовое изменение цен', 'products.drive' => 'Создана папка товара на Диске',
        'categories.store' => 'Добавлена категория', 'categories.update' => 'Изменена категория', 'categories.destroy' => 'Удалена категория',
        'categories.move' => 'Изменён порядок категорий', 'categories.drive' => 'Создана папка категории на Диске',
        'pages.store' => 'Добавлена страница', 'pages.update' => 'Изменена страница', 'pages.destroy' => 'Удалена страница',
        'banners.store' => 'Добавлен баннер', 'banners.update' => 'Изменён баннер', 'banners.destroy' => 'Удалён баннер',
        'characteristics.store' => 'Добавлена характеристика', 'characteristics.update' => 'Изменена характеристика', 'characteristics.destroy' => 'Удалена характеристика',
        'holidays.store' => 'Добавлен праздник', 'holidays.update' => 'Изменён праздник', 'holidays.destroy' => 'Удалён праздник',
        'settings.update' => 'Изменены настройки сайта', 'calculator.update' => 'Изменён калькулятор цен', 'novaposhta.update' => 'Изменены настройки Новой Почты',
        'backups.store' => 'Создана резервная копия', 'backups.destroy' => 'Удалена резервная копия',
        'users.update' => 'Изменён пользователь', 'users.destroy' => 'Удалён пользователь',
        'orders.status' => 'Изменён статус заказа', 'orders.paid' => 'Изменена отметка оплаты заказа', 'orders.destroy' => 'Удалён заказ',
        'orders.ttn.store' => 'Создана накладная', 'orders.ttn.refresh' => 'Обновлён статус накладной', 'orders.ttn.destroy' => 'Удалена накладная',
        'reviews.status' => 'Изменён статус отзыва', 'reviews.destroy' => 'Удалён отзыв',
    ];

    private const EDIT_ROUTES = [
        Product::class => 'admin.products.edit', Category::class => 'admin.categories.edit', Page::class => 'admin.pages.edit',
        Banner::class => 'admin.banners.edit', Characteristic::class => 'admin.characteristics.edit', Holiday::class => 'admin.holidays.edit',
        Order::class => 'admin.orders.show', User::class => 'admin.users.edit',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if ($user && $user->is_moderator && ! $user->is_admin && ! $request->isMethodSafe()
            && $response->getStatusCode() < 400 && ! $this->failedValidation($request)) {
            try {
                $this->log($request, $user, $response);
            } catch (\Throwable $e) {
                report($e); // журнал не должен ломать работу
            }
        }

        return $response;
    }

    private function failedValidation(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }
        $errors = $request->session()->get('errors');

        return $errors && method_exists($errors, 'any') && $errors->any();
    }

    private function log(Request $request, User $user, Response $response): void
    {
        $name = preg_replace('/^admin\./', '', (string) $request->route()?->getName());
        $subject = null;
        $url = null;

        foreach ($request->route()->parameters() as $param) {
            if ($param instanceof Model) {
                $subject = $param->getAttribute('name_ru') ?? $param->getAttribute('name') ?? $param->getAttribute('title_ru')
                    ?? $param->getAttribute('title') ?? $param->getAttribute('number') ?? ('#' . $param->getKey());
                if (! str_ends_with($name, '.destroy') && isset(self::EDIT_ROUTES[$param::class])) {
                    // Адрес без домена: администратор может открыть журнал с другого адреса (localhost, ngrok), и полный адрес вёл бы на «чужой» сайт без его сессии
                    $url = $param instanceof Product ? route('product', $param, false) : route(self::EDIT_ROUTES[$param::class], $param, false);
                }
                break;
            }
        }

        if ($subject === null) {
            $subject = $request->input('name_ru') ?? $request->input('title_ru') ?? $request->input('name_uk') ?? $request->input('title_uk');
        }
        if ($name === 'products.store' && ($product = Product::latest('id')->first())) {
            $url = route('product', $product, false); // только что созданный товар — на сайте
        }

        Activity::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'label' => self::LABELS[$name] ?? ('Действие: ' . $name),
            'subject' => $subject !== null ? mb_substr((string) $subject, 0, 250) : null,
            'url' => $url,
        ]);
    }
}
