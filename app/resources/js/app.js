'use strict';

import $ from 'jquery'; // resolves to jquery-global.js shim → window.jQuery

import 'bootstrap';
import { Tooltip } from 'bootstrap';
import 'admin-lte';
import 'select2';

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

    // Disable link after click
    $('.disable-after-click').on('click', function(e) {
        if ($(this).attr('disabled') === 'disabled') {
            e.preventDefault();
        }
        $(this).attr('disabled', 'disabled');
    });
});
