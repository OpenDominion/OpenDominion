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
    php artisan down --message="Updating" --retry=300

    git reset --hard origin/${branch}

    # Composer
    composer self-update

    if [[ ${env} == production ]]; then
        composer install --no-interaction --prefer-dist --no-dev
    else
        composer install --no-interaction --prefer-source
    fi

    # Artisan
    php artisan migrate --force

    php artisan clear-compiled
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear

    php artisan version:update

    # Npm packages
    if [[ ${env} == production ]]; then
        npm install --unsafe-perm --production
    else
        npm install --unsafe-perm
    fi

    # Frontend
    if [[ ${env} == production ]]; then
        npm run prod
    else
        npm run dev
    fi

    supervisorctl restart restart laravel-worker-beta.opendominion.net:*

    php artisan up
fi
