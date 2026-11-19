<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

/** Резервные копии: создать, скачать, удалить */
class BackupController extends Controller
{
    private function dir(): string
    {
        return storage_path('backups');
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

        return view('admin.backups', ['files' => $files, 'zip' => class_exists(\ZipArchive::class)]);
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
}
