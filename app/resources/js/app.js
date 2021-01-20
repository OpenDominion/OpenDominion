'use strict';

require('./momentum.js');
require('./bootstrap');

$(() => {

    // Ticker
    window.ticker.start();

    // AdminLTE tooltips
    $('[data-toggle="tooltip"]').tooltip({
        html: true,
    });

    // disable mousewheel on a input number field
    $('form').on('mousewheel', 'input[type=number]', function (e) {
        e.preventDefault();
    });

});
