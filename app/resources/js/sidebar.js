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
            document.body.style.overflow = document.body.classList.contains('sidebar-open') ? 'hidden' : '';
        } else {
            document.body.classList.toggle('sidebar-collapse');
            localStorage.setItem(COLLAPSE_KEY, document.body.classList.contains('sidebar-collapse') ? '1' : '0');
        }
    });

    // Overlay dismiss
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('sidebar-overlay')) {
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = '';
        }
    });

    // Forward overlay touch-scroll to the sidebar wrapper so the user
    // can scroll the menu by dragging anywhere on screen (including the
    // dimmed right half), and prevent the page behind from scrolling.
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        let overlayTouchStartY = 0;

        overlay.addEventListener('touchstart', (e) => {
            overlayTouchStartY = e.touches[0].clientY;
        }, { passive: true });

        overlay.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const wrapper = document.querySelector('.sidebar-wrapper');
            if (!wrapper) { return; }
            const delta = overlayTouchStartY - e.touches[0].clientY;
            overlayTouchStartY = e.touches[0].clientY;
            wrapper.scrollTop += delta;
        }, { passive: false });
    }

    // Hold-transition on resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        document.body.classList.add('hold-transition');
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => document.body.classList.remove('hold-transition'), 200);

        // If user resized from mobile (sidebar-open) to desktop, the inline
        // body.overflow='hidden' would otherwise leak and freeze page scroll.
        if (!isMobile()) {
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = '';
        }
    });

    // App loaded
    document.body.classList.add('app-loaded');
});
