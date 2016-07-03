var elixir = require('laravel-elixir');

elixir.config.css.outputFolder = 'assets/css';
elixir.config.js.outputFolder = 'assets/js';

var vendorFiles = {

    // jQuery
    'bower_components/jquery/dist/jquery.min.js': 'public/assets/vendor/jquery/js',

    // Bootstrap
    'bower_components/bootstrap/dist/css/*.min.css': 'public/assets/vendor/bootstrap/css',
    'bower_components/bootstrap/dist/fonts': 'public/assets/vendor/bootstrap/fonts',
    'bower_components/bootstrap/dist/js/bootstrap.min.js': 'public/assets/vendor/bootstrap/js',

    // Font Awesome
    'bower_components/font-awesome/css/font-awesome.min.css': 'public/assets/vendor/font-awesome/css',
    'bower_components/font-awesome/fonts': 'public/assets/vendor/font-awesome/fonts',

};

elixir(function (mix) {
    // Copy vendor assets
    for (var file in vendorFiles) {
        mix.copy(file, vendorFiles[file]);
    }

    // Compile app assets
    mix.sass('app.scss');
});
