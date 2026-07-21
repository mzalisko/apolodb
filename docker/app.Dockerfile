# CRM app runner: PHP 8.5 CLI + pdo_pgsql + redis ext + composer.
# Використовується docker-compose для artisan / pest / queue.
FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev git unzip \
    && docker-php-ext-install pdo pdo_pgsql bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
