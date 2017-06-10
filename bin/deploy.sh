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
    php bin/artisan down --message="Updating" --retry="300"

    git reset --hard origin/${branch}

    # Composer
    composer self-update

    if [[ ${env} == production ]]; then
        composer install --no-interaction --prefer-dist --no-dev
    else
        composer install --no-interaction --prefer-source
    fi

    # Artisan
    php bin/artisan migrate --force

    php bin/artisan clear-compiled
    php bin/artisan cache:clear
    php bin/artisan config:clear
    php bin/artisan view:clear

    php bin/artisan optimize

    php bin/artisan version:update

    # Npm packages
    yarn install

    # Frontend
    if [[ ${env} == production ]]; then
        npm run production
    else
        npm run dev
    fi

    php bin/artisan up
fi
