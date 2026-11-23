<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сохранение загруженных фото с уменьшением и пережатием (экономия места на диске и трафика).
 * Если расширение GD недоступно или файл нельзя обработать, сохраняется оригинал.
 */
class ImageStore
{
    /**
     * Адрес загруженного файла. Строится от адреса, по которому сейчас открыт сайт
     * (локально, через туннель или на домене), а не от APP_URL — иначе при другом адресе
     * в APP_URL фото перестают открываться.
     */
    public static function url(string $path): string
    {
        return url('storage/' . ltrim($path, '/'));
    }

    public static function store(UploadedFile $file, string $dir, int $maxSide = 1600, int $quality = 82): string
    {
        if (!function_exists('imagecreatefromstring')) {
            return $file->store($dir, 'public');
        }

        try {
            $original = (string) file_get_contents($file->getRealPath());
            $src = @imagecreatefromstring($original);
            if (!$src) {
                return $file->store($dir, 'public');
            }

            // Поворот по EXIF (фото с телефона)
            if (function_exists('exif_read_data') && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg'], true)) {
                $exif = @exif_read_data($file->getRealPath());
                $angle = match ($exif['Orientation'] ?? 1) {
                    3 => 180, 6 => -90, 8 => 90, default => 0,
                };
                if ($angle) {
                    $rotated = imagerotate($src, $angle, 0);
                    if ($rotated) {
                        $src = $rotated;
                    }
                }
            }

            [$w, $h] = [imagesx($src), imagesy($src)];
            $scale = min(1, $maxSide / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $useWebp = function_exists('imagewebp');
            $dst = imagecreatetruecolor($nw, $nh);
            if ($useWebp) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
            } else {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // JPEG без прозрачности: белый фон
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            $useWebp ? imagewebp($dst, null, $quality) : imagejpeg($dst, null, $quality);
            $data = (string) ob_get_clean();

            // Если пережатие не помогло (картинка и так лёгкая и не уменьшалась) — оставляем оригинал
            if ($data === '' || ($scale >= 1 && strlen($data) >= strlen($original))) {
                return $file->store($dir, 'public');
            }

            $path = trim($dir, '/') . '/' . Str::random(40) . ($useWebp ? '.webp' : '.jpg');
            Storage::disk('public')->put($path, $data);

            return $path;
        } catch (\Throwable $e) {
            return $file->store($dir, 'public');
        }
    }
}
