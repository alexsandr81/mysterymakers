<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Папки на Google Диске по структуре сайта:
 *   MysteryMakers / Категория / Подкатегория / «11000 Название товара»  (папка товара пустая — файлы вы кладёте сами)
 * Ошибки Диска никогда не ломают сохранение на сайте — они пишутся в журнал.
 */
class DriveSync
{
    /** Подпапки, которые раньше создавались внутри папки товара; команда shop:drive-flatten убирает их, а файлы переносит выше */
    public const OLD_SUBFOLDERS = ['Фото', 'Чертежи'];

    /** Создать/обновить папку категории. Возвращает id папки или null (выключено/ошибка). */
    public static function category(Category $category): ?string
    {
        return self::safe(fn () => self::categoryFolder($category));
    }

    public static function product(Product $product): ?string
    {
        return self::safe(fn () => self::productFolder($product));
    }

    /** Фраза для сообщения в админке, если папка не создалась */
    public static function note(?string $id): string
    {
        return GoogleDrive::enabled() && ! $id
            ? ' Но папку на Google Диске создать не удалось (подробности: storage/logs/laravel.log).'
            : '';
    }

    /** Все категории и товары без папок (или с устаревшими) — для команды shop:drive-sync */
    public static function all(?callable $progress = null): array
    {
        $ok = $fail = 0;
        $count = function (?string $id) use (&$ok, &$fail) {
            $id ? $ok++ : $fail++;
        };

        $cats = Category::orderByRaw('parent_id is not null')->orderBy('sort')->orderBy('id')->get();
        foreach ($cats as $c) {
            $count(self::category($c));
            $progress && $progress('Категория: ' . $c->name_ru);
        }
        foreach (Product::with('category')->orderBy('id')->get() as $p) {
            $count(self::product($p));
            $progress && $progress('Товар: ' . $p->sku . ' ' . $p->name_ru);
        }

        return [$ok, $fail];
    }

    /**
     * Убирает старые подпапки «Фото» и «Чертежи» внутри папок товаров: файлы переносятся в папку товара,
     * пустая подпапка уходит в корзину Диска. Подпапки с другими названиями не трогаются.
     * Возвращает [перенесено файлов, убрано подпапок, ошибок].
     */
    public static function flatten(?callable $progress = null): array
    {
        $moved = $removed = $errors = 0;
        if (! GoogleDrive::enabled()) {
            return [0, 0, 0];
        }
        $drive = new GoogleDrive();

        foreach (Product::whereNotNull('drive_folder_id')->orderBy('id')->get() as $product) {
            try {
                foreach ($drive->children($product->drive_folder_id) as $sub) {
                    if (! $drive->isFolder($sub) || ! in_array(trim($sub['name']), self::OLD_SUBFOLDERS, true)) {
                        continue;
                    }
                    foreach ($drive->children($sub['id']) as $item) {
                        $drive->move($item['id'], $sub['id'], $product->drive_folder_id);
                        $moved++;
                    }
                    // Подпапка уходит в корзину, только если после переноса она действительно пуста
                    if (! $drive->children($sub['id'])) {
                        $drive->trash($sub['id']);
                        $removed++;
                    }
                    $progress && $progress($product->sku . ': «' . $sub['name'] . '» перенесена в папку товара');
                }
            } catch (Throwable $e) {
                $errors++;
                Log::warning('Google Диск (перенос из подпапок), товар ' . $product->sku . ': ' . $e->getMessage());
                $progress && $progress($product->sku . ': ОШИБКА (подробности в storage/logs/laravel.log)');
            }
        }

        return [$moved, $removed, $errors];
    }

    private static function safe(callable $fn): ?string
    {
        if (! GoogleDrive::enabled()) {
            return null;
        }
        try {
            return $fn();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response->status() === 404) {
                Log::warning('Google Диск: папка не найдена или к ней нет доступа. Проверьте GOOGLE_DRIVE_ROOT_ID и что папка MysteryMakers открыта (роль «Редактор») для ' . (GoogleDrive::serviceEmail() ?: 'сервисного аккаунта'));
            } else {
                Log::warning('Google Диск: ' . $e->getMessage());
            }

            return null;
        } catch (Throwable $e) {
            Log::warning('Google Диск: ' . $e->getMessage());

            return null;
        }
    }

    private static function categoryFolder(Category $category): string
    {
        $drive = new GoogleDrive();
        $parentId = $category->parent_id && ($parent = Category::find($category->parent_id))
            ? self::categoryFolder($parent)
            : GoogleDrive::rootId();

        [$id] = $drive->ensureFolder($category->drive_folder_id, (string) $category->name_ru, $parentId);
        if ($id !== $category->drive_folder_id) {
            $category->forceFill(['drive_folder_id' => $id])->saveQuietly();
        }

        return $id;
    }

    private static function productFolder(Product $product): string
    {
        $drive = new GoogleDrive();
        $parentId = self::categoryFolder($product->category);

        [$id] = $drive->ensureFolder($product->drive_folder_id, trim($product->sku . ' ' . $product->name_ru), $parentId);
        if ($id !== $product->drive_folder_id) {
            $product->forceFill(['drive_folder_id' => $id])->saveQuietly();
        }

        return $id;
    }
}
