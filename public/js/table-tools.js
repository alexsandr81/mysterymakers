/*
 * Инструменты таблиц админки:
 *  - показать/скрыть столбцы (чекбоксы «⚙ Столбцы»);
 *  - менять ширину столбцов перетаскиванием края заголовка (как в Excel).
 * Настройки хранятся в браузере (localStorage) отдельно для каждой таблицы.
 *
 * Разметка: <table data-tools="ключ" data-default-hidden="created">,
 * в <th> и <td> скрываемых столбцов — data-col="имя".
 * Чекбоксы: <input type="checkbox" data-colpick="имя" data-table="ключ">, кнопка сброса: data-colpick-reset="ключ".
 */
(function () {
    var MIN = 48, FALLBACK = 110;

    function read(key, def) {
        try { var v = JSON.parse(localStorage.getItem(key)); if (v !== null && v !== undefined) return v; } catch (e) {}
        return def;
    }
    function write(key, val) { try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) {} }

    function init(table) {
        var name = table.dataset.tools;
        var HKEY = 'mm_cols_' + name, WKEY = 'mm_widths_' + name;
        var defHidden = (table.dataset.defaultHidden || '').split(',').filter(Boolean);
        var picks = Array.prototype.slice.call(document.querySelectorAll('[data-colpick][data-table="' + name + '"]'));
        var heads = Array.prototype.slice.call(table.querySelectorAll('thead th'));

        function hiddenList() { return read(HKEY, defHidden.slice()); }
        function widths() { return read(WKEY, {}); }

        function applyHidden() {
            var hidden = hiddenList();
            picks.forEach(function (c) { c.checked = hidden.indexOf(c.dataset.colpick) === -1; });
            Array.prototype.forEach.call(table.querySelectorAll('[data-col]'), function (el) {
                el.hidden = hidden.indexOf(el.dataset.col) !== -1;
            });
        }

        function applyWidths() {
            var w = widths(), keys = Object.keys(w);
            if (!keys.length) {
                table.classList.remove('is-fixed');
                table.style.width = '';
                heads.forEach(function (th) { th.style.width = ''; });
                return;
            }
            var total = 0;
            heads.forEach(function (th) {
                if (th.hidden) return;
                var px = w[th.dataset.col || ''] || FALLBACK;
                th.style.width = px + 'px';
                total += px;
            });
            table.classList.add('is-fixed');
            table.style.width = total + 'px';
        }

        // Первый раз тянем столбец — «замораживаем» текущие ширины всех видимых столбцов
        function freeze() {
            var w = widths();
            heads.forEach(function (th) {
                if (!th.hidden && th.dataset.col && !w[th.dataset.col]) w[th.dataset.col] = Math.round(th.getBoundingClientRect().width);
            });
            write(WKEY, w);
            applyWidths();
        }

        heads.forEach(function (th) {
            if (!th.dataset.col || th.hasAttribute('data-nores')) return;
            var grip = document.createElement('span');
            grip.className = 'col-grip';
            grip.title = 'Потяните, чтобы изменить ширину (двойной клик — сбросить)';
            th.appendChild(grip);

            grip.addEventListener('dblclick', function () {
                var w = widths(); delete w[th.dataset.col];
                if (Object.keys(w).length <= 1) w = {};
                write(WKEY, w); applyWidths();
            });
            grip.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                if (!Object.keys(widths()).length) freeze();
                var key = th.dataset.col, startX = e.clientX, startW = th.getBoundingClientRect().width;
                grip.setPointerCapture(e.pointerId);
                table.classList.add('is-resizing');
                function move(ev) {
                    var nw = Math.max(MIN, Math.round(startW + ev.clientX - startX));
                    var w = widths(); w[key] = nw; write(WKEY, w); applyWidths();
                }
                function up() {
                    grip.removeEventListener('pointermove', move);
                    grip.removeEventListener('pointerup', up);
                    grip.removeEventListener('pointercancel', up);
                    table.classList.remove('is-resizing');
                }
                grip.addEventListener('pointermove', move);
                grip.addEventListener('pointerup', up);
                grip.addEventListener('pointercancel', up);
            });
        });

        picks.forEach(function (c) {
            c.addEventListener('change', function () {
                write(HKEY, picks.filter(function (x) { return !x.checked; }).map(function (x) { return x.dataset.colpick; }));
                applyHidden(); applyWidths();
            });
        });
        var reset = document.querySelector('[data-colpick-reset="' + name + '"]');
        if (reset) reset.addEventListener('click', function () {
            try { localStorage.removeItem(HKEY); localStorage.removeItem(WKEY); } catch (e) {}
            applyHidden(); applyWidths();
        });

        applyHidden(); applyWidths();
    }

    Array.prototype.forEach.call(document.querySelectorAll('table[data-tools]'), init);
})();
