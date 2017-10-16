'use strict';

require('./bootstrap');

const ticker = require('./ticker');
ticker();

$(function () {
    $('[data-toggle="tooltip"]').tooltip({
        html: true,
    });
});
