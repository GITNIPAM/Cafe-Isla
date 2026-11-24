#!/usr/bin/env bash
set -e

PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH"
fi

php artisan config:clear
php artisan migrate --force

# Seed demo data only if the users table is empty
USER_COUNT=$(php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    echo Illuminate\Support\Facades\DB::table("users")->count();
} catch (Throwable $e) {
    echo "0";
}
')

if [ "$USER_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

php artisan storage:link || true

exec apache2-foreground
