let mix = require('laravel-mix');

// For mix-manifest.json
mix.setPublicPath('public');

const vendorDirs = {

    // AdminLTE
    'node_modules/admin-lte/dist': 'public/assets/vendor/admin-lte',
    'node_modules/admin-lte/plugins': 'public/assets/vendor/admin-lte/plugins',

    // Bootstrap
    'node_modules/bootstrap/dist': 'public/assets/vendor/bootstrap',

    // Chart.js
    'node_modules/chart.js/dist/Chart.bundle.min.js': 'public/assets/vendor/chart.js/Chart.bundle.min.js',

    // DataTables
    'node_modules/datatables.net/js': 'public/assets/vendor/datatables/js',
    'node_modules/datatables.net-bs/css': 'public/assets/vendor/datatables/css',
    'node_modules/datatables.net-bs/js': 'public/assets/vendor/datatables/js',

    // Font Awesome
    'node_modules/font-awesome/css': 'public/assets/vendor/font-awesome/css',
    'node_modules/font-awesome/fonts': 'public/assets/vendor/font-awesome/fonts',

    // jQuery
    'node_modules/jquery/dist': 'public/assets/vendor/jquery',

    // RPG Awesome
    'node_modules/rpg-awesome/css': 'public/assets/vendor/rpg-awesome/css',
    'node_modules/rpg-awesome/fonts': 'public/assets/vendor/rpg-awesome/fonts',

    // Select2
    'node_modules/select2/dist/css': 'public/assets/vendor/select2/css',
    'node_modules/select2/dist/js': 'public/assets/vendor/select2/js',

};

for (const dir in vendorDirs) {
    mix.copyDirectory(dir, vendorDirs[dir]);
}

mix.copy('app/resources/images', 'public/assets/app/images');

mix.js('app/resources/js/app.js', 'public/assets/app/js')
    .sass('app/resources/sass/app.scss', 'public/assets/app/css');

if (mix.inProduction()) {
    mix.version();
} else {
    mix.sourceMaps();
}
