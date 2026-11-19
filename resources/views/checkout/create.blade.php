@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', __('shop.checkout'))

@section('content')
<h1>{{ __('shop.checkout') }}</h1>

@error('cart')<div class="flash flash--err">{{ $message }}</div>@enderror

<div class="checkout">
    <form action="{{ route('checkout.store') }}" method="POST" class="form">
        @csrf
        <h3>{{ __('shop.contact_data') }}</h3>

        @php
            // Если клиент вошёл в аккаунт, подставляем имя из профиля (первое слово — имя, остальное — фамилия)
            $parts = preg_split('/\s+/u', trim((string) $user?->name), 2);
        @endphp
        <div class="form__cols">
            <label>{{ __('shop.last_name') }}
                <input type="text" name="last_name" value="{{ old('last_name', $parts[1] ?? '') }}" required autocomplete="family-name">
                @error('last_name')<span class="err">{{ $message }}</span>@enderror
            </label>
            <label>{{ __('shop.first_name') }}
                <input type="text" name="first_name" value="{{ old('first_name', $parts[0] ?? '') }}" required autocomplete="given-name">
                @error('first_name')<span class="err">{{ $message }}</span>@enderror
            </label>
        </div>
        <label>{{ __('shop.phone') }}
            <input type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" placeholder="+380501234567" required>
            @error('phone')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email', $user?->email) }}">
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>

        <h3>{{ __('shop.delivery_method') }}</h3>
        @php $deliv = old('delivery', 'nova_poshta'); @endphp
        <div class="choice-list">
            @foreach(\App\Models\Order::DELIVERY as $key)
                <label class="choice">
                    <input type="radio" name="delivery" value="{{ $key }}" @checked($deliv === $key)>
                    <span>{{ __('shop.delivery_types.' . $key) }}</span>
                </label>
            @endforeach
        </div>
        @error('delivery')<span class="err">{{ $message }}</span>@enderror

        {{-- Новая Почта --}}
        <fieldset class="delivery-box" data-delivery="nova_poshta">
            <label>{{ __('shop.np_city') }}
                <div class="ac">
                    <input type="text" name="np_city" id="np-city" value="{{ old('np_city') }}" autocomplete="off" placeholder="{{ $npEnabled ? __('shop.np_city_ph') : '' }}">
                    <ul class="ac__list" id="np-city-list" hidden></ul>
                </div>
                @error('np_city')<span class="err">{{ $message }}</span>@enderror
                @error('np_city_ref')<span class="err">{{ __('shop.np_pick_from_list') }}</span>@enderror
            </label>
            <input type="hidden" name="np_city_ref" id="np-city-ref" value="{{ old('np_city_ref') }}">

            <label>{{ __('shop.np_warehouse') }}
                <div class="ac">
                    <input type="text" name="np_warehouse" id="np-wh" value="{{ old('np_warehouse') }}" autocomplete="off" placeholder="{{ $npEnabled ? __('shop.np_wh_ph') : __('shop.np_wh_manual') }}">
                    <ul class="ac__list" id="np-wh-list" hidden></ul>
                </div>
                @error('np_warehouse')<span class="err">{{ $message }}</span>@enderror
                @error('np_warehouse_ref')<span class="err">{{ __('shop.np_pick_from_list') }}</span>@enderror
            </label>
            <input type="hidden" name="np_warehouse_ref" id="np-wh-ref" value="{{ old('np_warehouse_ref') }}">
        </fieldset>

        {{-- Укрпошта --}}
        <fieldset class="delivery-box" data-delivery="ukrposhta">
            <label>{{ __('shop.up_city') }}
                <input type="text" name="up_city" value="{{ old('up_city') }}">
                @error('up_city')<span class="err">{{ $message }}</span>@enderror
            </label>
            <label>{{ __('shop.up_index') }}
                <input type="text" name="up_index" value="{{ old('up_index') }}" inputmode="numeric" maxlength="5" pattern="\d{5}" placeholder="01001">
                <span class="muted">{{ __('shop.up_index_hint') }} <a href="https://index.ukrposhta.ua/" target="_blank" rel="noopener">index.ukrposhta.ua</a></span>
                @error('up_index')<span class="err">{{ $message }}</span>@enderror
            </label>
        </fieldset>

        <h3>{{ __('shop.payment_method') }}</h3>
        @php $pay = old('payment', 'cod'); @endphp
        <div class="choice-list choice-list--col">
            @foreach(\App\Models\Order::PAYMENT as $key)
                <label class="choice">
                    <input type="radio" name="payment" value="{{ $key }}" @checked($pay === $key)>
                    <span>
                        {{ __('shop.payment_types.' . $key) }}
                        <small class="muted">{{ __('shop.payment_hint.' . $key) }}</small>
                    </span>
                </label>
            @endforeach
        </div>
        @error('payment')<span class="err">{{ $message }}</span>@enderror

        <label>{{ __('shop.comment') }}
            <textarea name="comment" rows="3">{{ old('comment') }}</textarea>
        </label>

        <button type="submit" class="btn btn--accent btn--lg">{{ __('shop.confirm_order') }}</button>
    </form>

    <aside class="summary">
        <h3>{{ __('shop.cart') }}</h3>
        @foreach($items as $row)
            <div class="summary__row">
                <span>{{ $row['product']->name }} × {{ $row['quantity'] }}</span>
                <span>{{ Money::format($row['subtotal']) }}</span>
            </div>
        @endforeach
        <div class="summary__total">{{ __('shop.total') }}: <strong>{{ Money::format($total) }} {{ __('shop.currency') }}</strong></div>
    </aside>
</div>
<script>
(function () {
    // Показываем только поля выбранной службы доставки; скрытые отключаем, чтобы не мешали отправке
    var radios = document.querySelectorAll('input[name="delivery"]');
    var boxes = document.querySelectorAll('.delivery-box');
    var afterSync = function () {};
    function syncDelivery() {
        var current = document.querySelector('input[name="delivery"]:checked');
        current = current ? current.value : 'nova_poshta';
        boxes.forEach(function (box) {
            var on = box.dataset.delivery === current;
            box.hidden = !on;
            box.querySelectorAll('input').forEach(function (i) {
                i.disabled = !on;
                if (i.type !== 'hidden') i.required = on && !i.classList.contains('optional');
            });
        });
        afterSync();
    }
    radios.forEach(function (r) { r.addEventListener('change', syncDelivery); });
    syncDelivery();

    @if($npEnabled)
    // Выбор города и отделения Новой Почты из списка
    var cityInput = document.getElementById('np-city'), cityRef = document.getElementById('np-city-ref'), cityList = document.getElementById('np-city-list');
    var whInput = document.getElementById('np-wh'), whRef = document.getElementById('np-wh-ref'), whList = document.getElementById('np-wh-list');
    var citiesUrl = @json(route('np.cities')), whUrl = @json(route('np.warehouses'));

    function debounce(fn, ms) { var t; return function () { var a = arguments; clearTimeout(t); t = setTimeout(function () { fn.apply(null, a); }, ms); }; }

    function render(list, items, onPick) {
        list.innerHTML = '';
        if (!items.length) { list.hidden = true; return; }
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.textContent = item.title;
            li.addEventListener('mousedown', function (e) { e.preventDefault(); onPick(item); list.hidden = true; });
            list.appendChild(li);
        });
        list.hidden = false;
    }
    function load(url, cb) {
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : []; }).then(cb).catch(function () { cb([]); });
    }

    function setWhEnabled(on) { whInput.disabled = !on || whInput.closest('.delivery-box').hidden; }
    afterSync = function () { setWhEnabled(!!cityRef.value); };
    afterSync();

    cityInput.addEventListener('input', debounce(function () {
        cityRef.value = ''; whRef.value = ''; whInput.value = ''; setWhEnabled(false);
        var q = cityInput.value.trim();
        if (q.length < 2) { cityList.hidden = true; return; }
        load(citiesUrl + '?q=' + encodeURIComponent(q), function (items) {
            render(cityList, items, function (item) {
                cityInput.value = item.title; cityRef.value = item.ref; setWhEnabled(true); whInput.focus();
            });
        });
    }, 250));

    function loadWh() {
        if (!cityRef.value) return;
        load(whUrl + '?city=' + encodeURIComponent(cityRef.value) + '&q=' + encodeURIComponent(whInput.value.trim()), function (items) {
            render(whList, items, function (item) { whInput.value = item.title; whRef.value = item.ref; });
        });
    }
    whInput.addEventListener('focus', loadWh);
    whInput.addEventListener('input', debounce(function () { whRef.value = ''; loadWh(); }, 250));

    [[cityInput, cityList], [whInput, whList]].forEach(function (p) {
        p[0].addEventListener('blur', function () { setTimeout(function () { p[1].hidden = true; }, 150); });
    });

    // Не даём отправить форму, если город/отделение набраны вручную, а не выбраны из списка
    document.querySelector('.checkout form').addEventListener('submit', function (e) {
        var np = document.querySelector('input[name="delivery"]:checked');
        if (np && np.value === 'nova_poshta' && (!cityRef.value || !whRef.value)) {
            e.preventDefault();
            alert(@json(__('shop.np_pick_from_list')));
        }
    });
    @endif
})();
</script>
@endsection
