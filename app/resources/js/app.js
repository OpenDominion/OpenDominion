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

});
