<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:WaveHack/OpenDominion.git');
set('branch', 'master');

set('ssh_multiplexing', false);

// Hosts

host('opendominion.net')
    ->set('deploy_path', '/var/www/opendominion.net')
    ->configFile('~/.ssh/config');

// Task definitions

desc('Installing Laravel Nova');
task('nova install', function () {
    run('cd {{release_path}} && rm -rf nova vendor/laravel/nova');
    run('cd {{release_path}} && ln -s storage/keys/composer-nova.json ./auth.json');
    run('cd {{release_path}} && {{bin/composer}} update --no-dev --no-scripts laravel/nova');
    run('cd {{release_path}} && {{bin/php}} artisan nova:publish');
});

desc('Installing NPM dependencies');
task('npm install', function () {
    if (has('previous_release')) {
        run('cp -R {{previous_release}}/node_modules {{release_path}}/node_modules');
    }

    run('cd {{release_path}} && npm install');
});

desc('Build frontend');
task('npm run prod', function () {
    run('cd {{release_path}} && npm run prod');
});

desc('Update version');
task('artisan:version:update', function () {
    run('cd {{release_path}} && {{bin/php}} artisan version:update');
});

desc('Reload php-fpm');
task('php-fpm:reload', function () {
    run('sudo service php7.2-fpm reload');
});

desc('Restart supervisor workers');
task('supervisorctl:restart', function () {
    run('sudo supervisorctl restart all');
});

// Execute tasks

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'nova install', //
    'npm install', //
    'npm run prod', //
    'deploy:writable',
    'artisan:storage:link',
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:optimize',
    'artisan:down', //
    'artisan:migrate', //
    'artisan:version:update', //
    'deploy:symlink',
    'php-fpm:reload', //
    'supervisorctl:restart', //
    'artisan:up', //
    'deploy:unlock',
    'cleanup',
]);

after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'artisan:up');
