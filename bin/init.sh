#!/bin/bash

# Assumes the files .env.template.(local|production|testing) to be present in the root directory

if [[ ! $# -eq 1 ||  ! $1 =~ ^(local|production|testing)$ ]]; then
    echo "Usage: $0 (local|production|testing)"
    exit 1;
fi

# Composer
composer self-update

if [[ ! -d vendor ]]; then
    if [[ $1 == production ]]; then
        composer install --no-interaction --prefer-dist --no-dev
    else
        composer install --no-interaction --prefer-source

        if [[ $1 == local ]] && grep -q laravel-ide-helper composer.json; then
            IDE_HELPERS=1
        fi
    fi
fi

# Dotenv
if [[ ! -f .env ]]; then
    cp ".env.template.$1" .env

    # Generate app key
    php artisan key:generate
fi

# Setup/seed database + IDE files on local
if [[ $1 == local ]] && [[ ! -f storage/databases/local.sqlite ]]; then
    touch storage/databases/local.sqlite
    php artisan migrate --seed
fi

# Local IDE Helpers
if [[ $IDE_HELPERS ]]; then
    php artisan clear-compiled
    php artisan ide-helper:generate
    php artisan ide-helper:models -N
    php artisan ide-helper:meta
    php artisan optimize
fi

# Frontend stuff, not needed during testing
if [[ ! $1 == testing ]]; then

    # Npm packages
    [[ -d node_modules ]] || npm install
    # todo: npm insttall --production

    # Bower
    [[ -f bower.json && ! -d bower_components ]] && bower install

    # Gulp
    if [[ $1 == production ]]; then
        gulp --production
    else
        gulp
    fi

fi

# Show message on production
if [[ $1 == production ]]; then
    echo "Don't forget to setup your .env file and run 'php artisan migrate --seed'"
fi

echo "Done"
