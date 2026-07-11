'use strict';

const tour = document.querySelector('[data-new-player-tour]');

if (tour) {
    const key = tour.dataset.tourTarget;
    const pageTarget = document.querySelector(`[data-onboarding-target="${key}"]`);
    const navTarget = document.querySelector(`[data-onboarding-nav="${tour.dataset.tourNav}"]`);
    const collapseButton = tour.querySelector('[data-tour-collapse]');
    const dragHandle = tour.querySelector('[data-tour-drag-handle]');
    const showPageButton = tour.querySelector('[data-tour-show-page]');
    const resumeButton = document.querySelector('[data-tour-resume]');
    const desktop = window.matchMedia('(min-width: 768px)');
    const positionStorageKey = `new-player-tour-position:${key}`;
    const clamp = (value, maximum) => Math.min(Math.max(16, value), Math.max(16, maximum));
    let dragPosition = null;
    let drag = null;
    let positionFrame = null;

    try {
        dragPosition = JSON.parse(sessionStorage.getItem(positionStorageKey));
    } catch {
        dragPosition = null;
    }
    if (!Number.isFinite(dragPosition?.left) || !Number.isFinite(dragPosition?.top)) {
        dragPosition = null;
    }

    pageTarget?.classList.add('new-player-tour-target');
    navTarget?.classList.add('new-player-tour-nav-target');

    const position = () => {
        if (!desktop.matches) {
            tour.style.removeProperty('--tour-top');
            tour.style.removeProperty('--tour-left');
            return;
        }

        if (dragPosition) {
            const left = clamp(dragPosition.left, window.innerWidth - tour.offsetWidth - 16);
            const top = clamp(dragPosition.top, window.innerHeight - tour.offsetHeight - 16);
            dragPosition = { left, top };
            tour.style.setProperty('--tour-top', `${top}px`);
            tour.style.setProperty('--tour-left', `${left}px`);
            return;
        }

        if (!pageTarget) {
            return;
        }

        const target = pageTarget.getBoundingClientRect();
        const width = Math.min(360, window.innerWidth - 32);
        const right = target.right + 16;
        const hasRightSpace = right + width <= window.innerWidth - 16;
        const hasLeftSpace = target.left - width - 16 >= 16;
        let left = hasRightSpace ? right : hasLeftSpace ? target.left - width - 16 : 16;
        let top = clamp(target.top, window.innerHeight - tour.offsetHeight - 16);
        const above = target.top - tour.offsetHeight - 16;
        if (!hasRightSpace && !hasLeftSpace && above >= 16) {
            left = clamp(target.left, window.innerWidth - width - 16);
            top = above;
        }

        tour.style.setProperty('--tour-top', `${top}px`);
        tour.style.setProperty('--tour-left', `${left}px`);
    };

    const schedulePosition = () => {
        if (positionFrame !== null) {
            return;
        }

        positionFrame = requestAnimationFrame(() => {
            positionFrame = null;
            position();
        });
    };

    const moveDrag = event => {
        if (!drag) {
            return;
        }

        dragPosition = {
            left: drag.left + event.clientX - drag.x,
            top: drag.top + event.clientY - drag.y,
        };
        schedulePosition();
    };

    const finishDrag = () => {
        if (!drag) {
            return;
        }

        drag = null;
        tour.classList.remove('new-player-tour--dragging');
        try {
            sessionStorage.setItem(positionStorageKey, JSON.stringify(dragPosition));
        } catch {
            // Position persistence is optional; dragging still works without it.
        }
    };

    dragHandle?.addEventListener('mousedown', event => {
        if (!desktop.matches || event.button !== 0) {
            return;
        }

        event.preventDefault();
        const bounds = tour.getBoundingClientRect();
        drag = { x: event.clientX, y: event.clientY, left: bounds.left, top: bounds.top };
        tour.classList.add('new-player-tour--dragging');
    });
    window.addEventListener('mousemove', moveDrag);
    window.addEventListener('mouseup', finishDrag);

    const collapse = ({ keepPageHighlight = false, focusResume = true } = {}) => {
        tour.hidden = true;
        resumeButton.hidden = false;
        if (!keepPageHighlight) {
            pageTarget?.classList.remove('new-player-tour-target');
        }
        navTarget?.classList.remove('new-player-tour-nav-target');
        if (focusResume) {
            resumeButton.focus();
        }
    };

    const showPage = () => {
        collapse({ keepPageHighlight: true, focusResume: false });
        if (!pageTarget) {
            resumeButton.focus();
            return;
        }

        pageTarget.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'start',
        });
        if (!pageTarget.hasAttribute('tabindex')) {
            pageTarget.setAttribute('tabindex', '-1');
            pageTarget.dataset.tourTemporaryTabindex = 'true';
        }
        pageTarget.focus({ preventScroll: true });
    };

    const resume = () => {
        tour.hidden = false;
        resumeButton.hidden = true;
        pageTarget?.classList.add('new-player-tour-target');
        navTarget?.classList.add('new-player-tour-nav-target');
        if (pageTarget?.dataset.tourTemporaryTabindex === 'true') {
            pageTarget.removeAttribute('tabindex');
            delete pageTarget.dataset.tourTemporaryTabindex;
        }
        schedulePosition();
        collapseButton.focus();
    };

    collapseButton.addEventListener('click', collapse);
    showPageButton.addEventListener('click', showPage);
    resumeButton.addEventListener('click', resume);
    window.addEventListener('resize', schedulePosition, { passive: true });
    window.addEventListener('scroll', schedulePosition, { passive: true });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !tour.hidden) {
            collapse();
        }
    });

    schedulePosition();
}
