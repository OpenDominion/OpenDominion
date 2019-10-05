#!/usr/bin/env sh

# Update Composer packages
composer install

# Clear Laravel caches
php artisan optimize:clear

# Migrate database if needed
php artisan migrate

# Sync static game data to the database
php artisan game:data:sync

# Update app version
php artisan version:update

# Generate new IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models -N
php artisan ide-helper:meta

# Update NPM packages
yarn install

# Rebuild frontend assets
yarn run dev

# Diagnostics check
php artisan self-diagnosis


