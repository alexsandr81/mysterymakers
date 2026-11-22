<button type="button" class="{{ $class ?? 'theme-btn' }}" id="theme-toggle" title="{{ $title ?? 'Тема' }}" aria-label="{{ $title ?? 'Тема' }}">🌙</button>
<script>
(function () {
    var btn = document.getElementById('theme-toggle'), root = document.documentElement;
    function paint() { btn.textContent = root.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙'; }
    btn.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('mm_theme', next); } catch (e) {}
        paint();
    });
    paint();
})();
</script>
