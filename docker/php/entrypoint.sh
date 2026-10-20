#!/bin/sh
set -eu

mkdir -p /var/www/storage/attachments
chown www-data:www-data /var/www/storage /var/www/storage/attachments
chmod 750 /var/www/storage /var/www/storage/attachments

if [ "${1:-}" = "apache2-foreground" ]; then
    php /var/www/html/api/migrate.php
fi

exec docker-php-entrypoint "$@"
