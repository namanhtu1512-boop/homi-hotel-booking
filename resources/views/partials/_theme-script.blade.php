<script>
    (function () {
        var stored = localStorage.getItem('homi-theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var isDark = stored ? stored === 'dark' : prefersDark;
        document.documentElement.classList.toggle('dark', isDark);
    })();

    function homiToggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('homi-theme', isDark ? 'dark' : 'light');
    }
</script>
