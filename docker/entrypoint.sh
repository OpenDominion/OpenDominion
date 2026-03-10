#!/usr/bin/env bash
set -e

echo "==> Fixing storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Setting up .env..."
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    echo "    Copied .env.example to .env"
fi

echo "==> Installing PHP dependencies..."
if [ ! -d /var/www/html/vendor ]; then
    composer install --no-interaction --prefer-dist --no-scripts
fi

echo "==> Generating application key..."
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    php artisan key:generate --no-interaction
fi

echo "==> Running database migrations..."
php artisan migrate --no-interaction --force

echo "==> Seeding database..."
SEEDED=$(php -r "try { \$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); echo \$pdo->query('SELECT COUNT(*) FROM round_leagues')->fetchColumn(); } catch(Throwable \$e) { echo 0; }" 2>/dev/null)
if [ "$SEEDED" = "0" ]; then
    php artisan db:seed --no-interaction
    php artisan game:data:sync
fi

echo "==> Installing Node dependencies..."
if [ ! -d /var/www/html/node_modules ]; then
    npm install
fi

echo "==> Compiling assets..."
if [ ! -f /var/www/html/public/mix-manifest.json ]; then
    npm run dev
fi

echo "==> Starting Apache..."
exec apache2-foreground
