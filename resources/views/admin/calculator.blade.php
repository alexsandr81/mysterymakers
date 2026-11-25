@extends('admin.layout')

@section('title', 'Калькулятор цены')

@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $js = $materials->map(fn ($m) => [
        'id' => $m->id, 'label' => $m->label(), 'sw' => $m->sheet_w, 'sh' => $m->sheet_h,
        'price' => (float) $m->sheet_price, 'cut' => (float) $m->cut_rate,
    ])->values();
@endphp

@section('content')
<h1>🧮 Калькулятор цены изделия</h1>
<p class="muted">Фанера и МДФ: материал + резка + гравировка + прочее. 3D-печать: расход филамента × цена за грамм + прочее. Дальше → себестоимость → накладные расходы → наценка → цена. Все ставки ниже можно менять.</p>

<div class="calc">
    <section class="calc__box">
        <h3>Расчёт</h3>
        <div class="calc__grid">
            <label>Что считаем
                <select id="c-kind">
                    <option value="laser" selected>Изделие из фанеры / МДФ (лазер)</option>
                    <option value="print">3D-печать (филамент)</option>
                </select>
            </label>
            <label class="js-print" hidden>Расход филамента, г <small class="muted">вес изделия вместе с поддержками и отходами, из слайсера</small> <input type="number" id="c-grams" min="0" step="any" value="50"></label>
            <p class="muted js-print" hidden>Цена филамента: <strong id="c-fprice-note">—</strong> за грамм (меняется в параметрах расчёта ниже). Катушку 1 кг за 700 грн считайте как 0,7 грн/г.</p>
            <label class="js-laser">Материал
                <select id="c-material">
                    @foreach($materials as $m)
                        <option value="{{ $m->id }}">{{ $m->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="js-laser">Как задать размер
                <select id="c-mode">
                    <option value="area" selected>Площадь из раскладки, см²</option>
                    <option value="size">Габариты: ширина × высота, мм</option>
                </select>
            </label>
            <label class="js-area js-laser">Площадь изделия в раскладке, см² <input type="number" id="c-area" min="0" step="any" value="100"></label>
            <label class="js-size js-laser" hidden>Ширина изделия, мм <input type="number" id="c-w" min="0" step="any" value="100"></label>
            <label class="js-size js-laser" hidden>Высота изделия, мм <input type="number" id="c-h" min="0" step="any" value="100"></label>
            <label class="check-line js-laser"><input type="checkbox" id="c-waste"> Добавить обрезки листа (использование <span id="c-waste-pct">{{ $fmt($p['calc_waste']) }}</span>%)</label>
            <label class="js-laser">Длина реза, мм <input type="number" id="c-cut" min="0" step="any" value="400"></label>
            <label class="js-laser">Площадь гравировки, см² <input type="number" id="c-engr" min="0" step="any" value="0"></label>
            <label>Прочее на изделие, грн <small class="muted">упаковка, фурнитура, покраска</small> <input type="number" id="c-extra" min="0" step="any" value="0"></label>
            <label>Количество, шт <input type="number" id="c-qty" min="1" step="1" value="1"></label>
        </div>
        <p class="muted js-laser">Площадь, длину реза и площадь гравировки берите из раскладки. «Площадь из раскладки» — сколько см² листа занимает изделие вместе с зазорами между деталями; тогда галочку «обрезки» обычно не нужно ставить, они уже учтены. Для режима «габариты» галочка включается сама.</p>

        <table class="table calc__result">
            <tbody>
                <tr class="js-laser"><td>Материал (с учётом обрезков)</td><td id="r-material">—</td></tr>
                <tr class="js-laser"><td>Резка</td><td id="r-cut">—</td></tr>
                <tr class="js-laser"><td>Гравировка</td><td id="r-engr">—</td></tr>
                <tr class="js-print" hidden><td>Филамент (<span id="r-grams">0</span> г)</td><td id="r-filament">—</td></tr>
                <tr><td>Прочее</td><td id="r-extra">—</td></tr>
                <tr><td><strong>Себестоимость</strong></td><td id="r-cost"><strong>—</strong></td></tr>
                <tr><td>С накладными расходами</td><td id="r-over">—</td></tr>
                <tr class="calc__price"><td><strong>Цена изделия</strong></td><td id="r-price">—</td></tr>
                <tr><td>Прибыль с изделия</td><td id="r-profit">—</td></tr>
                <tr id="r-total-row"><td>Итого за <span id="r-qty">1</span> шт.</td><td id="r-total">—</td></tr>
            </tbody>
        </table>
        <p class="muted" id="r-warn"></p>
    </section>

    <form action="{{ route('admin.calculator.update') }}" method="POST" class="calc__box">
        @csrf @method('PUT')
        <h3>Параметры расчёта</h3>
        <div class="calc__grid">
            <label>Использование листа, % <small class="muted">остальное — обрезки и отходы</small>
                <input type="number" name="calc_waste" min="10" max="100" step="any" value="{{ old('calc_waste', $fmt($p['calc_waste'])) }}" required></label>
            <label>Гравировка, грн за см²
                <input type="number" name="calc_engr_rate" min="0" step="any" value="{{ old('calc_engr_rate', $fmt($p['calc_engr_rate'])) }}" required></label>
            <label>Филамент для 3D-печати, грн за грамм <small class="muted">цена катушки ÷ вес катушки в граммах</small>
                <input type="number" name="calc_filament_price" min="0" step="any" value="{{ old('calc_filament_price', $fmt($p['calc_filament_price'])) }}" required></label>
            <label>Накладные расходы, % <small class="muted">электричество, износ станка и принтера, аренда</small>
                <input type="number" name="calc_overhead" min="0" step="any" value="{{ old('calc_overhead', $fmt($p['calc_overhead'])) }}" required></label>
            <label>Наценка, %
                <input type="number" name="calc_margin" min="0" step="any" value="{{ old('calc_margin', $fmt($p['calc_margin'])) }}" required></label>
            <label>Минимальная цена изделия, грн <small class="muted">0 — без минимума</small>
                <input type="number" name="calc_min_price" min="0" step="any" value="{{ old('calc_min_price', $fmt($p['calc_min_price'])) }}" required></label>
            <label>Округлять цену вверх до, грн <small class="muted">0 — не округлять</small>
                <input type="number" name="calc_round" min="0" step="any" value="{{ old('calc_round', $fmt($p['calc_round'])) }}" required></label>
        </div>

        <h3>Материалы</h3>
        <p class="muted">Цена листа и тариф резки за погонный метр. Дата показывает, когда цену листа меняли в последний раз — «не проверялась» значит цену нужно сверить с поставщиком.</p>
        <div class="table-wrap">
        <table class="table calc__materials" id="mat-table">
            <thead><tr><th>Материал</th><th>Толщ., мм</th><th>Лист, мм (ш × в)</th><th>Цена листа, грн</th><th>Резка, грн/м</th><th>Что за лист / ссылка</th><th>Цена проверена</th><th>Удалить</th></tr></thead>
            <tbody>
            @php $rows = old('materials', $materials->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'thickness' => $fmt($m->thickness), 'sheet_w' => $m->sheet_w, 'sheet_h' => $m->sheet_h, 'sheet_price' => $fmt($m->sheet_price), 'cut_rate' => $fmt($m->cut_rate), 'link' => $m->link])->all()); @endphp
            @foreach($rows as $i => $m)
                @php $date = !empty($m['id']) ? optional($materials->firstWhere('id', $m['id']))->price_date : null; @endphp
                <tr>
                    <td><input type="hidden" name="materials[{{ $i }}][id]" value="{{ $m['id'] ?? '' }}"><input type="text" name="materials[{{ $i }}][name]" value="{{ $m['name'] }}" required></td>
                    <td><input type="number" step="any" name="materials[{{ $i }}][thickness]" value="{{ $m['thickness'] }}" required class="w-sm"></td>
                    <td class="nowrap"><input type="number" name="materials[{{ $i }}][sheet_w]" value="{{ $m['sheet_w'] }}" required class="w-md"> × <input type="number" name="materials[{{ $i }}][sheet_h]" value="{{ $m['sheet_h'] }}" required class="w-md"></td>
                    <td><input type="number" step="any" name="materials[{{ $i }}][sheet_price]" value="{{ $m['sheet_price'] }}" required class="w-md"></td>
                    <td><input type="number" step="any" name="materials[{{ $i }}][cut_rate]" value="{{ $m['cut_rate'] }}" required class="w-sm"></td>
                    <td><input type="text" name="materials[{{ $i }}][link]" value="{{ $m['link'] ?? '' }}"></td>
                    <td class="{{ $date ? 'muted' : 'text-warn' }}">{{ $date ? $date->format('d.m.Y') : (empty($m['id']) ? 'сегодня' : 'не проверялась') }}</td>
                    <td><input type="checkbox" name="materials[{{ $i }}][delete]" value="1"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <p><button type="button" class="btn" id="mat-add">+ Добавить материал</button></p>

        <div><button type="submit" class="btn btn--accent btn--lg">Сохранить параметры</button></div>
    </form>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var tbody = document.querySelector('#mat-table tbody');
    var PARAMS = ['calc_waste', 'calc_engr_rate', 'calc_filament_price', 'calc_overhead', 'calc_margin', 'calc_min_price', 'calc_round'];
    var P = {}, M = [];
    var num = function (el) { return parseFloat(String(el && el.value).replace(',', '.')) || 0; };

    // Параметры и материалы читаются прямо из полей формы, поэтому пересчёт идёт «на лету», до сохранения
    function readForm() {
        PARAMS.forEach(function (k) { P[k] = num(document.querySelector('[name="' + k + '"]')); });
        M = [];
        Array.prototype.forEach.call(tbody.rows, function (tr, idx) {
            var f = function (name) { return tr.querySelector('[name$="[' + name + ']"]'); };
            if (f('delete') && f('delete').checked) return;
            var t = f('thickness').value;
            M.push({ id: String(idx), label: (f('name').value || 'Материал') + (t ? ' ' + t + ' мм' : ''),
                sw: num(f('sheet_w')), sh: num(f('sheet_h')), price: num(f('sheet_price')), cut: num(f('cut_rate')) });
        });
        // Список материалов в расчёте — по таблице, выбранный сохраняется
        var sel = $('c-material'), cur = sel.value;
        var html = M.map(function (m) { return '<option value="' + m.id + '">' + m.label.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>'; }).join('');
        if (sel.innerHTML !== html) { sel.innerHTML = html; }
        if (M.some(function (m) { return m.id === cur; })) sel.value = cur;
        $('c-waste-pct').textContent = P.calc_waste;
    }
    var n = function (id) { return parseFloat(String($(id).value).replace(',', '.')) || 0; };
    var money = function (v) { return v.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' грн'; };

    // Что видно: фанера/МДФ (лазер) или 3D-печать
    function visibility() {
        var print = $('c-kind').value === 'print', area = $('c-mode').value === 'area';
        document.querySelectorAll('.js-laser').forEach(function (e) { e.hidden = print; });
        document.querySelectorAll('.js-print').forEach(function (e) { e.hidden = !print; });
        document.querySelectorAll('.js-area').forEach(function (e) { e.hidden = print || !area; });
        document.querySelectorAll('.js-size').forEach(function (e) { e.hidden = print || area; });
    }

    function calc() {
        readForm();
        var print = $('c-kind').value === 'print';
        var m = M.find(function (x) { return String(x.id) === $('c-material').value; });
        if (!print && !m) { $('r-warn').textContent = 'Добавьте хотя бы один материал.'; return; }
        var material = 0, cut = 0, engr = 0, filament = 0, areaMm2 = 0;
        if (print) {
            filament = n('c-grams') * P.calc_filament_price;
            $('c-fprice-note').textContent = money(P.calc_filament_price);
            $('r-grams').textContent = n('c-grams');
            $('r-filament').textContent = money(filament);
        } else {
            areaMm2 = $('c-mode').value === 'area' ? n('c-area') * 100 : n('c-w') * n('c-h');
            var usable = m.sw * m.sh * ($('c-waste').checked ? P.calc_waste / 100 : 1);
            material = usable > 0 ? areaMm2 / usable * m.price : 0;
            cut = n('c-cut') / 1000 * m.cut;
            engr = n('c-engr') * P.calc_engr_rate;
        }
        var extra = n('c-extra');
        var cost = material + cut + engr + filament + extra;
        var over = cost * (1 + P.calc_overhead / 100);
        var price = over * (1 + P.calc_margin / 100);
        if (P.calc_min_price > 0) price = Math.max(price, P.calc_min_price);
        if (P.calc_round > 0) price = Math.ceil(price / P.calc_round - 1e-9) * P.calc_round;
        var qty = Math.max(1, Math.floor(n('c-qty')));
        $('r-material').textContent = money(material);
        $('r-cut').textContent = money(cut);
        $('r-engr').textContent = money(engr);
        $('r-extra').textContent = money(extra);
        $('r-cost').innerHTML = '<strong>' + money(cost) + '</strong>';
        $('r-over').textContent = money(over);
        $('r-price').innerHTML = '<strong>' + money(price) + '</strong>';
        $('r-profit').textContent = money(price - over);
        $('r-qty').textContent = qty;
        $('r-total').textContent = money(price * qty);
        $('r-warn').textContent = (!print && areaMm2 > m.sw * m.sh) ? '⚠ Изделие больше листа выбранного материала.' : '';
    }
    ['c-material','c-area','c-w','c-h','c-cut','c-engr','c-extra','c-qty','c-waste','c-grams'].forEach(function (id) { $(id).addEventListener('input', calc); });
    $('c-mode').addEventListener('change', function () {
        $('c-waste').checked = this.value !== 'area'; // габариты — прибавляем обрезки, раскладка — уже учтены
        visibility();
        calc();
    });
    $('c-kind').addEventListener('change', function () { visibility(); calc(); });
    visibility();
    // Любое изменение в параметрах и таблице материалов сразу пересчитывает цену
    document.querySelector('form.calc__box').addEventListener('input', calc);
    document.querySelector('form.calc__box').addEventListener('change', calc);
    calc();

    // Добавление строки материала
    $('mat-add').addEventListener('click', function () {
        var i = tbody.rows.length + 1000 + Math.floor(Math.random() * 1000);
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="hidden" name="materials[' + i + '][id]" value=""><input type="text" name="materials[' + i + '][name]" required></td>' +
            '<td><input type="number" step="any" name="materials[' + i + '][thickness]" required class="w-sm"></td>' +
            '<td class="nowrap"><input type="number" name="materials[' + i + '][sheet_w]" value="1525" required class="w-md"> × <input type="number" name="materials[' + i + '][sheet_h]" value="1525" required class="w-md"></td>' +
            '<td><input type="number" step="any" name="materials[' + i + '][sheet_price]" required class="w-md"></td>' +
            '<td><input type="number" step="any" name="materials[' + i + '][cut_rate]" required class="w-sm"></td>' +
            '<td><input type="text" name="materials[' + i + '][link]"></td><td class="muted">сегодня</td><td></td>';
        tbody.appendChild(tr);
        calc();
    });
})();
</script>
@endsection
