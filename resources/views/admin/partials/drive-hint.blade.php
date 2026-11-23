@unless(\App\Services\GoogleDrive::enabled())
    <div class="flash flash--info">
        📁 Google Диск не подключён, поэтому папки не создаются.
        @if(!config('shop.drive.root_id'))
            Не задан <code>GOOGLE_DRIVE_ROOT_ID</code> в файле <code>.env</code>.
        @endif
        @unless(is_file((string) config('shop.drive.key_file')))
            Не найден файл ключа <code>storage\app\google-drive.json</code>.
        @endunless
        Инструкция: README, раздел 5.4.
    </div>
@endunless
