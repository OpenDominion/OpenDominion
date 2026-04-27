{{-- Apply stored color mode before CSS renders to prevent flash of wrong theme. --}}
<script>
(function () {
    var stored = localStorage.getItem('color-mode') || 'auto';
    var darkModes = ['dark', 'classic', 'dusk', 'grimoire'];
    var bsTheme = darkModes.indexOf(stored) !== -1 ? 'dark'
                : stored === 'auto'
                  ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : 'light';
    var scheme = (stored === 'auto') ? bsTheme : stored;
    document.documentElement.setAttribute('data-bs-theme', bsTheme);
    document.documentElement.setAttribute('data-color-mode', stored);
    document.documentElement.setAttribute('data-color-scheme', scheme);
})();
</script>
