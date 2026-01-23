<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:OpenDominion/OpenDominion.git');
set('branch', 'master');
set('update_code_strategy', 'clone');
set('git_tty', true);
set('keep_releases', 3);

// Hosts

host('opendominion.net')
    ->set('hostname', 'opendominion.net')
    ->set('remote_user', 'ec2-user')
    ->set('http_user', 'nginx')
    ->set('deploy_path', '/var/www/opendominion.net');

// Task definitions

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

desc('Syncing game data');
task('artisan:game:data:sync', function () {
    run('cd {{release_path}} && {{bin/php}} artisan game:data:sync');
});

desc('Update version');
task('artisan:version:update', function () {
    run('cd {{release_path}} && {{bin/php}} artisan version:update');
});

desc('Reload php-fpm');
task('php-fpm:reload', function () {
    run('sudo service php-fpm reload');
});

// Execute tasks

// Task list is based off the Laravel recipe
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'npm install', // custom made
    'npm run prod', // custom made
    'artisan:storage:link',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:view:cache',
    'artisan:event:cache',
    'artisan:migrate',
    'artisan:game:data:sync', // custom made
    'artisan:version:update', // custom made
    'deploy:publish',
    'php-fpm:reload', // custom made
]);
