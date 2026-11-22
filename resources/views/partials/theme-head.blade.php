{{-- Тема применяется до отрисовки страницы, чтобы не было вспышки светлого фона --}}
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('mm_theme'); } catch (e) {}
    if (t !== 'dark' && t !== 'light') {
        t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
