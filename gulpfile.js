var elixir = require('laravel-elixir');

elixir.config.appPath = 'src';
elixir.config.assetsPath = 'app/resources/assets';
elixir.config.viewPath = 'app/resources/views';
elixir.config.css.outputFolder = 'assets/app/css';
elixir.config.js.outputFolder = 'assets/app/js';

var vendorFiles = {

    // AdminLTE
    'node_modules/admin-lte/dist': 'public/assets/vendor/admin-lte',
    'node_modules/admin-lte/bootstrap': 'public/assets/vendor/admin-lte/bootstrap',
    'node_modules/admin-lte/plugins': 'public/assets/vendor/admin-lte/plugins',

    // Font Awesome
    'node_modules/font-awesome/css': 'public/assets/vendor/font-awesome/css',
    'node_modules/font-awesome/fonts': 'public/assets/vendor/font-awesome/fonts',

    // RPG Awesome
    'node_modules/rpg-awesome/css': 'public/assets/vendor/rpg-awesome/css',
    'node_modules/rpg-awesome/fonts': 'public/assets/vendor/rpg-awesome/fonts'

};

elixir(function (mix) {

    // Copy vendor assets
    for (var file in vendorFiles) {
        mix.copy(file, vendorFiles[file]);
    }

    // Compile app assets
    mix.sass('app.scss');

});
