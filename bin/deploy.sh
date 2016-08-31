#!/usr/bin/env bash

if [[ ! $# -eq 2 || ! $2 =~ ^(local|production)$ ]]; then
    echo "Usage: $0 [branch] (local|production)"
    exit 1;
fi

branch=$1
env=$2

cd $(dirname $0)/../

git fetch origin ${branch}
git checkout --force ${branch}

if [ $(git rev-list --max-count=1 ${branch}) != $(git rev-list --max-count=1 origin/${branch}) ]; then
    php bin/artisan down

    git reset --hard origin/${branch}

    # Composer
    composer self-update

    if [[ ${env} == production ]]; then
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
    if [[ ${env} == production ]]; then
        gulp --production
    else
        gulp
    fi

    php bin/artisan up
fi
