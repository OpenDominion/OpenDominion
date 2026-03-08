'use strict';

const STORAGE_KEY = 'color-mode';

const ICONS = {
    light:   'fa-sun',
    dark:    'fa-moon',
    classic: 'fa-shield-halved',
    auto:    'fa-circle-half-stroke',
};

const THEME_COLORS = {
    dark:    '#1a1a2e',
    classic: '#005566',
    light:   '#ffffff',
};

function getStoredMode() {
    return localStorage.getItem(STORAGE_KEY) || 'auto';
}

function resolveMode(mode) {
    if (mode === 'auto') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return mode;
}

function applyMode(mode) {
    // 'classic' is built on top of dark; everything else maps directly.
    const bsTheme = (mode === 'classic' || mode === 'dark') ? 'dark'
                  : (mode === 'auto')
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                  : 'light';

    document.documentElement.setAttribute('data-bs-theme', bsTheme);
    document.documentElement.setAttribute('data-color-mode', mode);

    if (mode === 'classic') {
        document.documentElement.setAttribute('data-color-scheme', 'classic');
    } else {
        document.documentElement.removeAttribute('data-color-scheme');
    }

    localStorage.setItem(STORAGE_KEY, mode);

    const metaThemeColor = document.getElementById('meta-theme-color');
    if (metaThemeColor) {
        metaThemeColor.setAttribute('content', THEME_COLORS[mode] ?? THEME_COLORS[bsTheme] ?? THEME_COLORS.light);
    }

    updateUI(mode);
}

function updateUI(mode) {
    const icon = document.getElementById('color-mode-icon');
    if (icon) {
        icon.className = `fa ${ICONS[mode]} fa-fw`;
    }

    document.querySelectorAll('[data-color-mode-value]').forEach(btn => {
        const active = btn.dataset.colorModeValue === mode;
        btn.classList.toggle('active', active);
        const check = btn.querySelector('.color-mode-check');
        if (check) check.style.visibility = active ? 'visible' : 'hidden';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateUI(getStoredMode());

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-color-mode-value]');
        if (btn) applyMode(btn.dataset.colorModeValue);
    });

    // Reapply when OS preference changes (only matters in 'auto' mode)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getStoredMode() === 'auto') applyMode('auto');
    });
});
