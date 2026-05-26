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

    // Prevent double-submit on opt-in forms
    $(document).on('submit', 'form.disable-after-click', function(e) {
        const $form = $(this);
        if ($form.data('submitted')) {
            e.preventDefault();
            return;
        }
        $form.data('submitted', true);
        // Disable submit buttons after the event so their name/value still posts
        setTimeout(() => $form.find(':submit').prop('disabled', true), 0);
    });
});
