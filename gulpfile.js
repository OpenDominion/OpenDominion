var elixir = require('laravel-elixir');

elixir.config.appPath = 'src';
elixir.config.assetsPath = 'app/resources/assets';
elixir.config.viewPath = 'app/resources/views';
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

    // RPG Awesome
    'bower_components/rpg-awesome/css/rpg-awesome.min.css': 'public/assets/vendor/rpg-awesome/css',
    'bower_components/rpg-awesome/fonts': 'public/assets/vendor/rpg-awesome/fonts',

    // Metis Menu
    'bower_components/metisMenu/dist/metisMenu.min.css': 'public/assets/vendor/metisMenu/css',
    'bower_components/metisMenu/dist/metisMenu.min.js': 'public/assets/vendor/metisMenu/js',

    // SB Admin 2
    'bower_components/startbootstrap-sb-admin-2/dist/css': 'public/assets/vendor/sb-admin-2/css',
    'bower_components/startbootstrap-sb-admin-2/dist/js': 'public/assets/vendor/sb-admin-2/js',

};

elixir(function (mix) {
    // Copy vendor assets
    for (var file in vendorFiles) {
        mix.copy(file, vendorFiles[file]);
    }

    // Compile app assets
    mix.sass('app.scss');
});
