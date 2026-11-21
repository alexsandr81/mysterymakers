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
        <select name="delivery" id="delivery-select" class="select">
            @foreach(\App\Models\Order::DELIVERY as $key)
                @continue($key === 'np_address' && !$npEnabled)
                <option value="{{ $key }}" @selected($deliv === $key)>{{ __('shop.delivery_types.' . $key) }}</option>
            @endforeach
        </select>
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
                <span class="muted" id="np-no-postomat" @if(!in_array(old('payment', 'cod'), \App\Models\Order::NO_POSTOMAT_PAYMENT, true)) hidden @endif>{{ __('shop.np_no_postomat_cod') }}</span>
                @error('np_warehouse')<span class="err">{{ $message }}</span>@enderror
                @error('np_warehouse_ref')<span class="err">{{ __('shop.np_pick_from_list') }}</span>@enderror
            </label>
            <input type="hidden" name="np_warehouse_ref" id="np-wh-ref" value="{{ old('np_warehouse_ref') }}">
            <input type="hidden" name="np_is_postomat" id="np-postomat" value="{{ old('np_is_postomat', 0) }}">
        </fieldset>

        {{-- Новая Почта: адресная доставка --}}
        @if($npEnabled)
        <fieldset class="delivery-box" data-delivery="np_address">
            <label>{{ __('shop.np_city') }}
                <div class="ac">
                    <input type="text" name="npa_city" id="npa-city" value="{{ old('npa_city') }}" autocomplete="off" placeholder="{{ __('shop.np_city_ph') }}">
                    <ul class="ac__list" id="npa-city-list" hidden></ul>
                </div>
                @error('npa_city')<span class="err">{{ $message }}</span>@enderror
                @error('npa_city_ref')<span class="err">{{ __('shop.np_pick_from_list') }}</span>@enderror
            </label>
            <input type="hidden" name="npa_city_ref" id="npa-city-ref" value="{{ old('npa_city_ref') }}">
            <input type="hidden" id="npa-settlement" value="">

            <label>{{ __('shop.np_street') }}
                <div class="ac">
                    <input type="text" name="npa_street" id="npa-street" value="{{ old('npa_street') }}" autocomplete="off" placeholder="{{ __('shop.np_street_ph') }}">
                    <ul class="ac__list" id="npa-street-list" hidden></ul>
                </div>
                @error('npa_street')<span class="err">{{ $message }}</span>@enderror
                @error('npa_street_ref')<span class="err">{{ __('shop.np_pick_street') }}</span>@enderror
            </label>
            <input type="hidden" name="npa_street_ref" id="npa-street-ref" value="{{ old('npa_street_ref') }}">

            <div class="form__cols">
                <label>{{ __('shop.np_building') }}
                    <input type="text" name="npa_building" value="{{ old('npa_building') }}" maxlength="20">
                    @error('npa_building')<span class="err">{{ $message }}</span>@enderror
                </label>
                <label>{{ __('shop.np_flat') }}
                    <input type="text" name="npa_flat" class="optional" value="{{ old('npa_flat') }}" maxlength="20">
                </label>
            </div>
        </fieldset>
        @endif

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
    var deliverySelect = document.getElementById('delivery-select');
    var boxes = document.querySelectorAll('.delivery-box');
    var afterSync = function () {};
    function syncDelivery() {
        var current = deliverySelect.value || 'nova_poshta';
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
    deliverySelect.addEventListener('change', syncDelivery);
    syncDelivery();

    @if($npEnabled)
    // Выбор города и отделения Новой Почты из списка
    var cityInput = document.getElementById('np-city'), cityRef = document.getElementById('np-city-ref'), cityList = document.getElementById('np-city-list');
    var whInput = document.getElementById('np-wh'), whRef = document.getElementById('np-wh-ref'), whList = document.getElementById('np-wh-list');
    var citiesUrl = @json(route('np.cities')), whUrl = @json(route('np.warehouses')), streetsUrl = @json(route('np.streets'));
    var postomat = document.getElementById('np-postomat');
    var noPostomatPay = @json(\App\Models\Order::NO_POSTOMAT_PAYMENT);
    function postomatBlocked() {
        var p = document.querySelector('input[name=payment]:checked');
        return !!p && noPostomatPay.indexOf(p.value) !== -1;
    }

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

    function searchCity() {
        var q = cityInput.value.trim();
        if (q.length < 2) { cityList.hidden = true; return; }
        load(citiesUrl + '?q=' + encodeURIComponent(q), function (items) {
            render(cityList, items, function (item) {
                cityInput.value = item.title; cityRef.value = item.ref; setWhEnabled(true); whInput.focus();
            });
        });
    }
    cityInput.addEventListener('input', debounce(function () {
        cityRef.value = ''; whRef.value = ''; whInput.value = ''; setWhEnabled(false);
        searchCity();
    }, 250));
    // Список снова открывается, если город ещё не выбран, а поле активно
    function reopenCity() { if (!cityRef.value) searchCity(); }
    cityInput.addEventListener('focus', reopenCity);
    cityInput.addEventListener('click', reopenCity);
    whInput.addEventListener('click', function () { if (!whRef.value) loadWh(); });

    function loadWh() {
        if (!cityRef.value) return;
        load(whUrl + '?city=' + encodeURIComponent(cityRef.value) + '&q=' + encodeURIComponent(whInput.value.trim()), function (items) {
            if (postomatBlocked()) { items = items.filter(function (i) { return !i.postomat; }); }
            render(whList, items, function (item) { whInput.value = item.title; whRef.value = item.ref; postomat.value = item.postomat ? 1 : 0; });
        });
    }
    whInput.addEventListener('focus', loadWh);
    // Сменили способ оплаты: если выбран почтомат, а он при этой оплате недоступен — очищаем выбор
    document.querySelectorAll('input[name=payment]').forEach(function (r) {
        r.addEventListener('change', function () {
            var hint = document.getElementById('np-no-postomat');
            if (hint) hint.hidden = !postomatBlocked();
            if (postomatBlocked() && postomat.value === '1') { whInput.value = ''; whRef.value = ''; postomat.value = 0; }
        });
    });
    whInput.addEventListener('input', debounce(function () { whRef.value = ''; postomat.value = 0; loadWh(); }, 250));

    // ---- Адресная доставка: город → улица (дом и квартира вводятся вручную)
    var aCity = document.getElementById('npa-city'), aCityRef = document.getElementById('npa-city-ref'), aCityList = document.getElementById('npa-city-list');
    var aSettle = document.getElementById('npa-settlement');
    var aStreet = document.getElementById('npa-street'), aStreetRef = document.getElementById('npa-street-ref'), aStreetList = document.getElementById('npa-street-list');
    if (aCity) {
        function searchACity() {
            var q = aCity.value.trim();
            if (q.length < 2) { aCityList.hidden = true; return; }
            load(citiesUrl + '?q=' + encodeURIComponent(q), function (items) {
                render(aCityList, items, function (item) {
                    aCity.value = item.title; aCityRef.value = item.ref; aSettle.value = item.settlement; aStreet.focus();
                });
            });
        }
        aCity.addEventListener('input', debounce(function () { aCityRef.value = ''; aSettle.value = ''; aStreetRef.value = ''; aStreet.value = ''; searchACity(); }, 250));
        aCity.addEventListener('focus', function () { if (!aCityRef.value) searchACity(); });
        function searchStreet() {
            var q = aStreet.value.trim();
            if (!aSettle.value || q.length < 2) { aStreetList.hidden = true; return; }
            load(streetsUrl + '?settlement=' + encodeURIComponent(aSettle.value) + '&q=' + encodeURIComponent(q), function (items) {
                render(aStreetList, items, function (item) { aStreet.value = item.title; aStreetRef.value = item.ref; });
            });
        }
        aStreet.addEventListener('input', debounce(function () { aStreetRef.value = ''; searchStreet(); }, 250));
        aStreet.addEventListener('focus', function () { if (!aStreetRef.value) searchStreet(); });
        [[aCity, aCityList], [aStreet, aStreetList]].forEach(function (p) {
            p[0].addEventListener('keydown', function (e) { if (e.key === 'Escape') p[1].hidden = true; });
        });
        document.addEventListener('mousedown', function (e) {
            [[aCity, aCityList], [aStreet, aStreetList]].forEach(function (p) {
                if (e.target !== p[0] && !p[1].contains(e.target)) p[1].hidden = true;
            });
        });
    }

    // Список остаётся открытым, пока не выбран пункт. Закрывается по Esc или клику вне поля и списка.
    [[cityInput, cityList], [whInput, whList]].forEach(function (p) {
        p[0].addEventListener('keydown', function (e) { if (e.key === 'Escape') p[1].hidden = true; });
    });
    document.addEventListener('mousedown', function (e) {
        [[cityInput, cityList], [whInput, whList]].forEach(function (p) {
            if (e.target !== p[0] && !p[1].contains(e.target)) p[1].hidden = true;
        });
    });

    // Не даём отправить форму, если город/отделение набраны вручную, а не выбраны из списка
    document.querySelector('.checkout form').addEventListener('submit', function (e) {
        if (deliverySelect.value === 'nova_poshta' && (!cityRef.value || !whRef.value)) {
            e.preventDefault();
            alert(@json(__('shop.np_pick_from_list')));
        }
        if (deliverySelect.value === 'np_address' && aCity && (!aCityRef.value || !aStreetRef.value)) {
            e.preventDefault();
            alert(@json(__('shop.np_pick_from_list')));
        }
    });
    @endif
})();
</script>
@endsection
