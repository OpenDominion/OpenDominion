<?php
namespace Deployer;

require 'recipe/laravel.php';

set('repository', 'git@github.com:WaveHack/OpenDominion.git');
set('branch', 'develop');

set('ssh_multiplexing', false);

host('opendominion.net')
    ->set('deploy_path', '/var/www/opendominion.net')
    ->configFile('~/.ssh/config');

task('npm install', function () {
    if (has('previous_release')) {
        run('cp -R {{previous_release}}/node_modules {{release_path}}/node_modules');
    }

    run('cd {{release_path}} && npm install');
});

task('npm run prod', function () {
    run('cd {{release_path}} && npm run prod');
});

task('artisan:version:update', function () {
    run('{{bin/php}} {{release_path}}/artisan version:update');
});

after('deploy:vendors', 'npm install');
after('npm install', 'npm run prod');

after('artisan:optimize', 'artisan:version:update');

before('deploy:symlink', 'artisan:migrate');

before('artisan:migrate', 'artisan:down');
after('deploy:symlink', 'artisan:up');

after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'artisan:up');
