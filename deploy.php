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
    run('sudo service php7.3-fpm reload');
});

desc('Restart supervisor workers');
task('supervisorctl:restart', function () {
    run('sudo supervisorctl restart all');
});

// Execute tasks

// Task list is based off the Laravel recipe
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'npm install', // custom made
    'npm run prod', // custom made
    'deploy:writable',
    'artisan:storage:link',
    'artisan:down', // custom inserted
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:migrate', // custom inserted
    'artisan:game:data:sync', // custom made
    'artisan:version:update', // custom made
    'artisan:optimize',
    'deploy:symlink',
    'php-fpm:reload', // custom made
    'supervisorctl:restart', // custom made
    'artisan:up', // custom inserted
    'deploy:unlock',
    'cleanup',
]);

after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'artisan:up');
