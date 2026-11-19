<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

/**
 * php artisan shop:backup — резервная копия базы (SQLite) и загруженных фото в один zip-файл
 * в папке storage/backups. Хранятся последние N копий (по умолчанию 10).
 */
class BackupCommand extends Command
{
    protected $signature = 'shop:backup {--keep=10 : сколько последних копий хранить}';

    protected $description = 'Резервная копия базы данных и загруженных фото';

    public function handle(): int
    {
        if (!class_exists(ZipArchive::class)) {
            $this->error('В PHP не включено расширение zip. Включите extension=zip в php.ini.');
            return self::FAILURE;
        }

        $dir = storage_path('backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $target = $dir . '/shop-' . now()->format('Y-m-d_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE) !== true) {
            $this->error('Не удалось создать архив ' . $target);
            return self::FAILURE;
        }

        // База данных
        if (config('database.default') === 'sqlite') {
            $db = config('database.connections.sqlite.database');
            if ($db && is_file($db)) {
                $zip->addFile($db, 'database.sqlite');
            } else {
                $this->warn('Файл базы SQLite не найден: ' . $db);
            }
        } else {
            $this->warn('База не SQLite: сделайте дамп средствами MySQL (mysqldump или панель хостинга) — он не входит в этот архив.');
        }

        // Загруженные фото
        $public = storage_path('app/public');
        $files = 0;
        if (is_dir($public)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($public, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), 'uploads/' . substr($file->getPathname(), strlen($public) + 1));
                    $files++;
                }
            }
        }

        $zip->close();

        // Удаляем старые копии
        $keep = max(1, (int) $this->option('keep'));
        $all = glob($dir . '/shop-*.zip') ?: [];
        rsort($all);
        foreach (array_slice($all, $keep) as $old) {
            @unlink($old);
        }

        $this->info('Готово: ' . $target . ' (фото: ' . $files . ', размер: ' . round(filesize($target) / 1024 / 1024, 2) . ' МБ)');
        $this->line('В архив не входит файл .env (в нём пароли) — храните его отдельно и в надёжном месте.');

        return self::SUCCESS;
    }
}
