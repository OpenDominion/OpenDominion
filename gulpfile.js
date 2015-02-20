var elixir = require('laravel-elixir');

elixir(function (mix) {
    mix
        // LESS
        .less('app.less', 'public/assets/css')

        // JavaScript
        .scripts([
            '../../bower_components/jquery/dist/jquery.min.js',
            '../../bower_components/bootstrap/dist/js/bootstrap.min.js',
            '../../bower_components/metisMenu/dist/metisMenu.min.js',
            '../../bower_components/raphael/raphael-min.js',
            '../../bower_components/morrisjs/morris.min.js',
            '../../bower_components/startbootstrap-sb-admin-2/dist/js/sb-admin-2.js',
            'app.js'
        ], 'public/assets/js/app.js')

        // Resources
        .copy('bower_components/font-awesome/fonts', 'public/assets/fonts')
    ;
});
