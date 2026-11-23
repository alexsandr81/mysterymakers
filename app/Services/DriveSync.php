<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Папки на Google Диске по структуре сайта:
 *   MysteryMakers / Категория / Подкатегория / «11000 Название товара» / Фото, Модели, Чертежи
 * Ошибки Диска никогда не ломают сохранение на сайте — они пишутся в журнал.
 */
class DriveSync
{
    public const PRODUCT_SUBFOLDERS = ['Фото', 'Модели', 'Чертежи'];

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

    private static function safe(callable $fn): ?string
    {
        if (! GoogleDrive::enabled()) {
            return null;
        }
        try {
            return $fn();
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

        [$id, $created] = $drive->ensureFolder($product->drive_folder_id, trim($product->sku . ' ' . $product->name_ru), $parentId);
        if ($id !== $product->drive_folder_id) {
            $product->forceFill(['drive_folder_id' => $id])->saveQuietly();
        }
        if ($created) {
            foreach (self::PRODUCT_SUBFOLDERS as $sub) {
                $drive->ensureFolder(null, $sub, $id);
            }
        }

        return $id;
    }
}
