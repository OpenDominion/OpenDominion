'use strict';

import $ from 'jquery'; // resolves to jquery-global.js shim → window.jQuery

import 'bootstrap';
import { Tooltip } from 'bootstrap';

import './sidebar.js';

import './momentum.js';
import './bootstrap-app.js';
import './color-mode.js';

$(() => {
    // Ticker
    window.ticker.start();

    // Bootstrap 5 tooltips (data-bs-toggle="tooltip")
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new Tooltip(el, { html: true });
    });

    // Disable mousewheel on number inputs
    $('form').on('mousewheel', 'input[type=number]', function (e) {
        e.preventDefault();
    });

    // Disable link/button after click (anchors and standalone buttons, not forms)
    $('.disable-after-click:not(form)').on('click', function(e) {
        if ($(this).attr('disabled') === 'disabled') {
            e.preventDefault();
        }
        $(this).attr('disabled', 'disabled');
    });

    // Track which submit button was clicked so we know which to show a spinner on
    $(document).on('click', 'form.disable-after-click :submit', function() {
        $(this).closest('form').data('clickedSubmit', this);
    });

    // Prevent double-submit on opt-in forms; show loading state on the clicked button
    $(document).on('submit', 'form.disable-after-click', function(e) {
        const $form = $(this);

        // Another handler cancelled the submit; the POST won't fire, so don't lock
        if (e.isDefaultPrevented()) {
            return;
        }

        if ($form.data('submitted')) {
            e.preventDefault();
            return;
        }
        $form.data('submitted', true);

        // Fall back to the first submit button (e.g. when Enter triggered the submit)
        const $clicked = $($form.data('clickedSubmit') || $form.find(':submit').first());

        // Defer disabling so submit button name/value still posts
        setTimeout(() => {
            $form.find(':submit').prop('disabled', true);
            if ($clicked.length) {
                $clicked.prepend('<i class="fa fa-spinner fa-spin me-1 js-submit-spinner"></i>');
            }
        }, 0);

        // Recovery watchdog: if navigation hasn't happened in 10s, restore the form
        setTimeout(() => {
            $form.data('submitted', false);
            $form.removeData('clickedSubmit');
            $form.find(':submit').prop('disabled', false);
            $form.find('.js-submit-spinner').remove();
        }, 10000);
    });
});
