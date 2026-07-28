FROM php:8.4-fpm-alpine
RUN apk add --no-cache bash git icu-dev oniguruma-dev libzip-dev zip unzip mysql-client \
 && docker-php-ext-install pdo_mysql mbstring bcmath intl zip pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist || true
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist \
 && chown -R www-data:www-data storage bootstrap/cache
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
