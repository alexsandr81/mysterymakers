@extends('admin.layout')

@section('title', 'Резервные копии')

@section('content')
<h1>Резервные копии</h1>

@if(session('error'))<div class="flash flash--err">{{ session('error') }}</div>@endif
@if($lastRestore && auth()->user()->is_admin)
    <div class="flash {{ request()->boolean('restored') ? 'flash--ok flash--big' : 'flash--info' }}">
        @if(request()->boolean('restored'))<strong>✅ Резервная копия восстановлена.</strong><br>@else<strong>Последнее восстановление</strong><br>@endif
        Копия: <strong>{{ $lastRestore['file'] }}</strong> (создана {{ date('d.m.Y H:i', $lastRestore['backup_time']) }}).
        Восстановлено: {{ date('d.m.Y H:i', $lastRestore['at']) }}.
        {{ $lastRestore['with_files'] ? 'Фото из копии: ' . $lastRestore['files'] . '.' : 'Фото не трогали.' }}
        @if(!empty($lastRestore['safety']))<br>Состояние до восстановления сохранено в копии <strong>{{ $lastRestore['safety'] }}</strong>: если передумаете, восстановитесь из неё.@endif
        @if(!empty($lastRestore['note']))<br><span class="text-warn">{{ $lastRestore['note'] }}</span>@endif
    </div>
@endif
@unless($zip)
    <div class="flash flash--err">В PHP не включено расширение zip. В Laragon: правый клик по значку → PHP → Extensions → zip, затем перезапустите сервер.</div>
@endunless

<p class="muted">В копию входят база данных (SQLite) и все загруженные фото. Восстановиться из копии может только администратор: кнопка «Восстановить» заменяет базу данными из копии (текущее состояние перед этим сохраняется отдельной копией). Файл .env (пароли) не входит — храните его отдельно. Скачанную копию лучше сохранить на другой диск или в облако.</p>

<form action="{{ route('admin.backups.store') }}" method="POST" style="margin:14px 0;">
    @csrf
    <button class="btn btn--accent" @disabled(!$zip)>💾 Создать резервную копию</button>
</form>

@if(auth()->user()->is_admin)
<form action="{{ route('admin.backups.upload') }}" method="POST" enctype="multipart/form-data" class="form form--inline" style="margin:0 0 14px;">
    @csrf
    <label>Загрузить ранее скачанную копию (.zip) <input type="file" name="backup" accept=".zip" required></label>
    <button class="btn">⬆ Загрузить</button>
    @error('backup')<span class="text-warn">{{ $message }}</span>@enderror
</form>
@endif

<div class="panel">
    <table class="table">
        <thead><tr><th>Файл</th><th>Создан</th><th>Размер</th><th></th></tr></thead>
        <tbody>
        @forelse($files as $f)
            <tr>
                <td>{{ $f['name'] }}</td>
                <td>{{ date('d.m.Y H:i', $f['time']) }}</td>
                <td>{{ $f['size'] }} МБ</td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('admin.backups.download', $f['name']) }}">Скачать</a>
                    @if(auth()->user()->is_admin)
                    <form action="{{ route('admin.backups.restore', $f['name']) }}" method="POST" style="display:inline" data-restore onsubmit="return confirm('Восстановить сайт из копии {{ $f['name'] }}?\n\nВсе изменения после {{ date('d.m.Y H:i', $f['time']) }} (товары, заказы, пользователи) будут потеряны. Перед этим автоматически создастся копия текущего состояния.\nПосле восстановления может понадобиться войти заново.')">
                        @csrf
                        <label class="check" style="display:inline-flex;margin:0 6px;"><input type="checkbox" name="with_files" value="1" checked> с фото</label>
                        <button class="link-btn">↩ Восстановить</button>
                    </form>
                    @endif
                    <form action="{{ route('admin.backups.destroy', $f['name']) }}" method="POST" style="display:inline" onsubmit="return confirm('Удалить копию?')">
                        @csrf @method('DELETE')
                        <button class="link-btn link-btn--danger">Удалить</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Копий пока нет.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<script>
    // После подтверждения показываем, что восстановление идёт, и не даём нажать дважды
    document.querySelectorAll('form[data-restore]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            setTimeout(function () {
                document.querySelectorAll('form[data-restore] button, form[data-restore] input').forEach(function (el) { el.disabled = true; });
                var b = f.querySelector('button'); if (b) b.textContent = '⏳ Восстанавливаю…';
            }, 0);
        });
    });
</script>
@endsection
