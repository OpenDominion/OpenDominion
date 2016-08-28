#!/usr/bin/env bash

if [[ ! $# -eq 1 ]]; then
    echo "Usage: $0 [branch]"
    exit 1;
fi

cd $(dirname $0)/../

git fetch origin $1
git checkout --force $1

if [ $(git rev-list --max-count=1 $1) != $(git rev-list --max-count=1 origin/$1) ]; then
    php bin/artisan down

    git reset --hard origin/$1

    # Composer
    sudo composer self-update

    if [[ $1 == "master" ]]; then
        composer install --no-interaction --prefer-dist --no-dev
    else
        composer install --no-interaction --prefer-source
    fi

    # Artisan stuff
    php bin/artisan migrate --force

    #php bin/artisan clear-compiled
    php bin/artisan cache:clear
    #php bin/artisan optimize

    # todo: php bin/artisan version: update

    # Npm packages
    npm install
    # todo: npm install --production?

    # Bower
    [[ -f bower.json ]] && bower install

    # Gulp
    if [[ $1 == "master" ]]; then
        gulp --production
    else
        gulp
    fi

    php aritisan up
fi
