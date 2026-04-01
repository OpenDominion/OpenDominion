'use strict';

// Sidebar toggle + layout JS.

const COLLAPSE_KEY = 'sidebar-collapsed';
const MOBILE_BREAKPOINT = 992;

function isMobile() {
    return window.innerWidth < MOBILE_BREAKPOINT;
}

function restoreState() {
    if (!isMobile() && localStorage.getItem(COLLAPSE_KEY) === '1') {
        document.body.classList.add('sidebar-collapse');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    restoreState();

    // Sidebar toggle button
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lte-toggle="sidebar"]');
        if (!btn) return;
        e.preventDefault();

        if (isMobile()) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapse');
            localStorage.setItem(COLLAPSE_KEY, document.body.classList.contains('sidebar-collapse') ? '1' : '0');
        }
    });

    // Overlay dismiss
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('sidebar-overlay')) {
            document.body.classList.remove('sidebar-open');
        }
    });

    // Hold-transition on resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        document.body.classList.add('hold-transition');
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => document.body.classList.remove('hold-transition'), 200);
    });

    // App loaded
    document.body.classList.add('app-loaded');
});
