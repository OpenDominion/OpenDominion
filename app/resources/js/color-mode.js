'use strict';

const STORAGE_KEY = 'color-mode';

const ICONS = {
    light:     'fa-sun',
    dark:      'fa-moon',
    classic:   'fa-shield-halved',
    parchment: 'fa-scroll',
    terminal:  'fa-terminal',
    auto:      'fa-circle-half-stroke',
};

const THEME_COLORS = {
    dark:      '#1a1a2e',
    classic:   '#005566',
    parchment: '#f5f0e1',
    terminal:  '#0a0a0a',
    light:     '#ffffff',
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
    // Determine the base Bootstrap theme (light or dark).
    const darkModes = ['classic', 'dark', 'terminal'];
    const bsTheme = darkModes.includes(mode) ? 'dark'
                  : (mode === 'auto')
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                  : 'light';

    document.documentElement.setAttribute('data-bs-theme', bsTheme);
    document.documentElement.setAttribute('data-color-mode', mode);

    const customSchemes = ['classic', 'parchment', 'terminal'];
    if (customSchemes.includes(mode)) {
        document.documentElement.setAttribute('data-color-scheme', mode);
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
