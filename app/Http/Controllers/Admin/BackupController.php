<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/** Резервные копии: создать, скачать, загрузить, удалить и восстановить (восстановление — только администратор) */
class BackupController extends Controller
{
    private function dir(): string
    {
        return storage_path('backups');
    }

    /** Запись о последнем восстановлении: лежит в storage (в копию не входит) и не зависит от сессии и базы */
    private function stateFile(): string
    {
        return storage_path('app/last-restore.json');
    }

    private function path(string $file): string
    {
        abort_unless(preg_match('/^shop-[0-9_-]+\.zip$/', $file) === 1, 404);
        $path = $this->dir() . '/' . $file;
        abort_unless(is_file($path), 404);

        return $path;
    }

    public function index()
    {
        $files = collect(glob($this->dir() . '/shop-*.zip') ?: [])
            ->sortDesc()
            ->map(fn ($f) => ['name' => basename($f), 'size' => round(filesize($f) / 1024 / 1024, 2), 'time' => filemtime($f)])
            ->values();

        $lastRestore = is_file($this->stateFile()) ? json_decode((string) file_get_contents($this->stateFile()), true) : null;

        return view('admin.backups', ['files' => $files, 'zip' => class_exists(\ZipArchive::class), 'lastRestore' => is_array($lastRestore) ? $lastRestore : null]);
    }

    public function store()
    {
        $code = Artisan::call('shop:backup');
        $out = trim(Artisan::output());

        return back()->with($code === 0 ? 'status' : 'error', $code === 0 ? 'Резервная копия создана.' : $out);
    }

    public function download(string $file)
    {
        return response()->download($this->path($file));
    }

    public function destroy(string $file)
    {
        @unlink($this->path($file));

        return back()->with('status', 'Копия удалена.');
    }

    /** Загрузить ранее скачанную копию (например, с другого компьютера), чтобы потом восстановиться из неё */
    public function upload(Request $request)
    {
        $request->validate(['backup' => ['required', 'file', 'mimes:zip', 'max:1048576']]);
        $file = $request->file('backup');

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true || $zip->locateName('database.sqlite') === false) {
            return back()->with('error', 'Это не копия магазина: в архиве нет файла database.sqlite.');
        }
        $zip->close();

        if (! is_dir($this->dir())) {
            mkdir($this->dir(), 0775, true);
        }
        $base = 'shop-' . now()->format('Y-m-d_His');
        $name = $base . '-1.zip';
        for ($i = 2; is_file($this->dir() . '/' . $name); $i++) {
            $name = $base . '-' . $i . '.zip';
        }
        $file->move($this->dir(), $name);

        return back()->with('status', 'Копия загружена: ' . $name . '. Теперь её можно восстановить.');
    }

    /**
     * Восстановление: база SQLite заменяется базой из копии, фото из копии (по галочке) докладываются в папку загрузок.
     * Перед заменой автоматически создаётся копия текущего состояния, а после — прогоняются миграции (старая копия подходит к новому коду).
     */
    public function restore(Request $request, string $file)
    {
        $path = $this->path($file);

        if (config('database.default') !== 'sqlite') {
            return back()->with('error', 'Восстановление из этого архива работает только с базой SQLite. Для MySQL импортируйте дамп через phpMyAdmin или панель хостинга.');
        }
        $db = config('database.connections.sqlite.database');
        if (! $db || $db === ':memory:' || ! is_file($db)) {
            return back()->with('error', 'Не найден файл базы SQLite' . ($db ? ': ' . $db : '') . '.');
        }
        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'В PHP не включено расширение zip.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true || $zip->locateName('database.sqlite') === false) {
            return back()->with('error', 'В архиве нет файла database.sqlite — это не копия магазина.');
        }

        // 1. Достаём базу из архива во временный файл и проверяем, что она целая
        $tmp = tempnam(sys_get_temp_dir(), 'mmdb');
        $in = $zip->getStream('database.sqlite');
        $out = fopen($tmp, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        if (! $this->isHealthySqlite($tmp)) {
            @unlink($tmp);
            $zip->close();

            return back()->with('error', 'База в этой копии повреждена или не от этого магазина, восстановление отменено — текущие данные не тронуты.');
        }

        // 2. Страховка: копия текущего состояния (чтобы можно было вернуться назад)
        $code = Artisan::call('shop:backup', ['--keep' => 1000]);
        if ($code !== 0) {
            @unlink($tmp);
            $zip->close();

            return back()->with('error', 'Не удалось создать страховочную копию текущего состояния, восстановление отменено: ' . trim(Artisan::output()));
        }

        $safety = collect(glob($this->dir() . '/shop-*.zip') ?: [])->sortByDesc(fn ($f) => filemtime($f))->map(fn ($f) => basename($f))->first();

        // 3. Подменяем базу (соединение закрываем, остатки журнала SQLite убираем)
        DB::disconnect();
        @unlink($db . '-wal');
        @unlink($db . '-shm');
        if (! copy($tmp, $db)) {
            @unlink($tmp);
            $zip->close();

            return back()->with('error', 'Не удалось записать файл базы. Текущая копия сохранена в списке — восстановитесь из неё, если сайт не работает.');
        }
        @unlink($tmp);

        // 4. Фото из копии (по галочке): докладываем в storage/app/public, существующие файлы перезаписываются
        $files = 0;
        if ($request->boolean('with_files')) {
            $public = storage_path('app/public');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (! str_starts_with($name, 'uploads/') || str_ends_with($name, '/')) {
                    continue;
                }
                $rel = substr($name, 8);
                if ($rel === '' || str_contains($rel, '..') || str_contains($rel, '\\') || str_starts_with($rel, '/') || preg_match('/^[A-Za-z]:/', $rel)) {
                    continue; // защита от выхода за пределы папки
                }
                $dest = $public . '/' . $rel;
                if (! is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0775, true);
                }
                $src = $zip->getStream($name);
                $dst = fopen($dest, 'wb');
                if ($src && $dst) {
                    stream_copy_to_stream($src, $dst);
                    $files++;
                }
                $src && fclose($src);
                $dst && fclose($dst);
            }
        }
        $zip->close();

        // 5. Приводим структуру базы к текущей версии сайта и сбрасываем кэш
        $note = '';
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            $note = ' Но миграции не выполнились (' . $e->getMessage() . ') — запустите php artisan migrate.';
        }
        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable $e) {
        }

        // Запоминаем результат в файле: сессия после замены базы может сброситься, а подтверждение должно остаться на странице
        @file_put_contents($this->stateFile(), json_encode([
            'file' => $file, 'backup_time' => filemtime($path), 'at' => time(),
            'with_files' => $request->boolean('with_files'), 'files' => $files,
            'safety' => $safety, 'note' => trim($note),
        ], JSON_UNESCAPED_UNICODE));

        return redirect()->route('admin.backups.index', ['restored' => 1]);
    }

    /** Файл — настоящая SQLite-база без ошибок, и в ней есть таблицы магазина */
    private function isHealthySqlite(string $file): bool
    {
        if (@file_get_contents($file, false, null, 0, 16) !== "SQLite format 3\0") {
            return false;
        }
        try {
            $pdo = new \PDO('sqlite:' . $file);
            $ok = $pdo->query('PRAGMA integrity_check')->fetchColumn() === 'ok';
            $tables = $pdo->query("select name from sqlite_master where type = 'table' and name in ('products', 'categories', 'users')")->fetchAll(\PDO::FETCH_COLUMN);
            $pdo = null;

            return $ok && count($tables) === 3;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
