<?php

namespace App\Console\Commands;

use App\Services\DriveSync;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;

/** php artisan shop:drive-flatten — убрать подпапки «Фото» и «Чертежи» в папках товаров, файлы перенести в папку товара */
class DriveFlattenCommand extends Command
{
    protected $signature = 'shop:drive-flatten {--yes : не спрашивать подтверждение}';

    protected $description = 'Перенести файлы из подпапок «Фото» и «Чертежи» в папку товара на Google Диске и убрать пустые подпапки';

    public function handle(): int
    {
        if (! GoogleDrive::enabled()) {
            $this->error('Google Диск не настроен (см. README, раздел 5.4).');

            return self::FAILURE;
        }

        $this->line('Файлы из подпапок «' . implode('» и «', DriveSync::OLD_SUBFOLDERS) . '» будут перенесены в папку товара, пустые подпапки уйдут в корзину Google Диска.');
        if (! $this->option('yes') && ! $this->confirm('Продолжить?', true)) {
            return self::SUCCESS;
        }

        [$moved, $removed, $errors] = DriveSync::flatten(fn ($line) => $this->line($line));
        $this->info("Готово. Файлов перенесено: {$moved}, подпапок убрано: {$removed}, ошибок: {$errors}.");

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
