const {mix} = require('laravel-mix');

mix.setPublicPath('public');


const vendorFiles = {

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

for (let file in vendorFiles) {
    mix.copy(file, vendorFiles[file], false);
}

// mix.copy('app/resources/assets/images', 'public/assets/app/images', false);

mix.js('app/resources/assets/js/app.js', 'public/assets/app/js')
    .sass('app/resources/assets/sass/app.scss', 'public/assets/app/css')
    .version();
