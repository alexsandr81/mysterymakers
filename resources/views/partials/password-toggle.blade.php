{{-- Глазок «Показать пароль» для всех полей пароля (работает в любом браузере) --}}
<script>
(function () {
    var showLabel = @json(__('shop.show_password'));
    var hideLabel = @json(__('shop.hide_password'));
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        var wrap = document.createElement('span');
        wrap.className = 'pw';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pw__btn';
        btn.textContent = '👁';
        btn.title = showLabel;
        btn.setAttribute('aria-label', showLabel);
        btn.addEventListener('click', function () {
            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            btn.textContent = hidden ? '🙈' : '👁';
            btn.title = btn.ariaLabel = hidden ? hideLabel : showLabel;
        });
        wrap.appendChild(btn);
    });
})();
</script>
