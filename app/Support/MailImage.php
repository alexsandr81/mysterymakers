<?php

namespace App\Support;

use App\Models\OrderItem;
use Illuminate\Support\Facades\Storage;

/** Уменьшенные копии фото товаров для писем (встраиваются в письмо, поэтому видны всегда) */
class MailImage
{
    public static function forItem(OrderItem $item): ?string
    {
        $relative = $item->image ?: ($item->product?->images[0] ?? null);
        if (!$relative) {
            return null;
        }

        $full = Storage::disk('public')->path($relative);

        return is_file($full) ? self::thumb($full) : null;
    }

    private static function thumb(string $full, int $max = 160): string
    {
        if (!function_exists('imagecreatefromstring')) {
            return $full;
        }

        try {
            $dir = storage_path('app/mail-thumbs');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $target = $dir . '/' . md5($full . filemtime($full)) . '.jpg';
            if (is_file($target)) {
                return $target;
            }

            $src = @imagecreatefromstring((string) file_get_contents($full));
            if (!$src) {
                return $full;
            }

            [$w, $h] = [imagesx($src), imagesy($src)];
            $scale = min(1, $max / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // прозрачность PNG -> белый фон
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagejpeg($dst, $target, 82);

            return $target;
        } catch (\Throwable $e) {
            return $full;
        }
    }
}
