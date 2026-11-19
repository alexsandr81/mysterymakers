@extends('admin.layout')

@section('title', 'Новая Почта')

@section('content')
<h1>Новая Почта — данные отправителя</h1>
<p class="muted">Эти данные подставляются в накладные (ТТН), которые создаются кнопкой в заказе. Ключ API берётся из файла .env (NOVAPOSHTA_API_KEY).</p>

@unless($enabled)
    <div class="flash flash--err">Ключ NOVAPOSHTA_API_KEY не указан в .env — накладные недоступны.</div>
@else
<form action="{{ route('admin.novaposhta.update') }}" method="POST" class="form" id="np-form">
    @csrf @method('PUT')

    <h3>Отправитель</h3>
    @if(!$senders)
        <div class="flash flash--err">Новая Почта не вернула отправителей. Проверьте ключ API и что в кабинете my.novaposhta.ua есть контрагент-отправитель.</div>
    @endif
    <label>Контрагент
        <select name="np_sender_ref" required>
            @foreach($senders as $s)
                <option value="{{ $s['Ref'] }}" @selected(old('np_sender_ref', $v['np_sender_ref']) === $s['Ref'])>{{ $s['Description'] ?? $s['Ref'] }}</option>
            @endforeach
        </select>
    </label>
    <label>Контактное лицо
        <select name="np_contact_ref" required>
            @foreach($contacts as $c)
                <option value="{{ $c['Ref'] }}" @selected(old('np_contact_ref', $v['np_contact_ref']) === $c['Ref'])>{{ $c['Description'] ?? $c['Ref'] }}</option>
            @endforeach
        </select>
    </label>
    <label>Телефон отправителя
        <input type="text" name="np_sender_phone" value="{{ old('np_sender_phone', $v['np_sender_phone']) }}" placeholder="0501234567" required>
    </label>

    <h3>Откуда отправляете</h3>
    <input type="hidden" name="np_city_ref" id="city-ref" value="{{ old('np_city_ref', $v['np_city_ref']) }}">
    <input type="hidden" name="np_wh_ref" id="wh-ref" value="{{ old('np_wh_ref', $v['np_wh_ref']) }}">
    <label>Город (начните вводить и выберите из списка)
        <input type="text" name="np_city_title" id="city" value="{{ old('np_city_title', $v['np_city_title']) }}" autocomplete="off" required>
        <select id="city-list" size="5" style="display:none"></select>
    </label>
    <label>Отделение (введите номер или улицу и выберите из списка)
        <input type="text" name="np_wh_title" id="wh" value="{{ old('np_wh_title', $v['np_wh_title']) }}" autocomplete="off" required>
        <select id="wh-list" size="6" style="display:none"></select>
    </label>

    <h3>Посылка по умолчанию</h3>
    <div class="form__cols">
        <label>Вес, кг
            <input type="number" step="0.1" min="0.1" name="np_weight" value="{{ old('np_weight', $v['np_weight']) }}" required>
        </label>
        <label>Описание груза
            <input type="text" name="np_description" maxlength="100" value="{{ old('np_description', $v['np_description']) }}" required>
        </label>
    </div>
    <button class="btn btn--accent">Сохранить</button>
</form>

<script>
(function () {
    var citiesUrl = @json(route('np.cities')), whUrl = @json(route('np.warehouses'));
    var city = document.getElementById('city'), cityList = document.getElementById('city-list'), cityRef = document.getElementById('city-ref');
    var wh = document.getElementById('wh'), whList = document.getElementById('wh-list'), whRef = document.getElementById('wh-ref');
    var t1, t2;

    function fill(list, items, input, ref, after) {
        list.innerHTML = '';
        items.forEach(function (it) {
            var o = document.createElement('option'); o.value = it.ref; o.textContent = it.title; list.appendChild(o);
        });
        list.style.display = items.length ? 'block' : 'none';
        list.onchange = list.onclick = function () {
            var o = list.options[list.selectedIndex]; if (!o) return;
            input.value = o.textContent; ref.value = o.value; list.style.display = 'none'; if (after) after();
        };
    }
    function get(url) { return fetch(url, {headers: {'Accept': 'application/json'}}).then(function (r) { return r.json(); }); }

    city.addEventListener('input', function () {
        cityRef.value = ''; whRef.value = ''; wh.value = '';
        clearTimeout(t1);
        t1 = setTimeout(function () {
            if (city.value.length < 2) return;
            get(citiesUrl + '?q=' + encodeURIComponent(city.value)).then(function (d) { fill(cityList, d, city, cityRef, searchWh); });
        }, 300);
    });
    function searchWh() {
        if (!cityRef.value) return;
        get(whUrl + '?city=' + cityRef.value + '&q=' + encodeURIComponent(wh.value)).then(function (d) { fill(whList, d, wh, whRef); });
    }
    wh.addEventListener('input', function () { whRef.value = ''; clearTimeout(t2); t2 = setTimeout(searchWh, 300); });
    wh.addEventListener('focus', searchWh);
})();
</script>
@endunless
@endsection
