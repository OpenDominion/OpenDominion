#!/usr/bin/env bash

# Assumes the files .env.template.(local|production|testing) to be present in the root directory

if [[ ! $# -eq 1 ||  ! $1 =~ ^(local|production|testing)$ ]]; then
    echo "Usage: $0 (local|production|testing)"
    exit 1;
fi

env=$1

# Composer
composer self-update

if [[ ! -d vendor ]]; then
    if [[ ${env} == production ]]; then
        composer install --no-interaction --prefer-dist --no-dev
    else
        composer install --no-interaction --prefer-source

        if [[ ${env} == local ]] && grep -q laravel-ide-helper composer.json; then
            IDE_HELPERS=1
        fi
    fi
fi

# Dotenv
if [[ ! -f .env ]]; then
    cp ".env.template.$env" .env

    # Generate app key
    php bin/artisan key:generate
fi

# Setup/seed database + IDE files on local
if [[ ${env} == local ]] && [[ ! -f app/storage/databases/local.sqlite ]]; then
    touch app/storage/databases/local.sqlite
    php bin/artisan migrate --seed
fi

# Local IDE Helpers
if [[ ${IDE_HELPERS} ]]; then
    php bin/artisan clear-compiled
    php bin/artisan ide-helper:generate
    php bin/artisan ide-helper:models -N --dir="src/Models"
    php bin/artisan ide-helper:meta
    php bin/artisan optimize
fi

# Frontend stuff, not needed during testing
if [[ ! ${env} == testing ]]; then

    # Npm packages
    [[ -d node_modules ]] || npm install
    # todo: npm insttall --production

    # Bower
    [[ -f bower.json && ! -d bower_components ]] && bower install

    # Gulp
    if [[ ${env} == production ]]; then
        gulp --production
    else
        gulp
    fi

fi

# Show message on production
if [[ ${env} == production ]]; then
    echo "Don't forget to setup your .env file and run 'php bin/artisan migrate --seed'"
fi

echo "Done"
