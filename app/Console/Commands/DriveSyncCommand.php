<?php

namespace App\Console\Commands;

use App\Services\DriveSync;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;

/** php artisan shop:drive-sync — создать на Google Диске папки для всех категорий и товаров, у которых их ещё нет */
class DriveSyncCommand extends Command
{
    protected $signature = 'shop:drive-sync';

    protected $description = 'Создать папки категорий и товаров на Google Диске';

    public function handle(): int
    {
        if (! GoogleDrive::enabled()) {
            $this->error('Google Диск не настроен: нужны GOOGLE_DRIVE_ROOT_ID в .env и файл ключа (см. README, раздел 5.4).');

            return self::FAILURE;
        }

        [$ok, $fail] = DriveSync::all(fn ($line) => $this->line($line));
        $this->info("Готово. Успешно: {$ok}, с ошибками: {$fail}.");
        if ($fail) {
            $this->warn('Подробности ошибок — в storage/logs/laravel.log');
        }

        return $fail ? self::FAILURE : self::SUCCESS;
    }
}
