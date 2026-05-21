# FurniStyle Symfony API — production image (nginx + PHP-FPM). Local dev: INSTALL_DEV_DEPS=1 via compose.
FROM php:8.3-fpm-bookworm

ARG INSTALL_DEV_DEPS=0

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    nginx \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        opcache \
        gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer install --no-interaction --prefer-dist --no-scripts; \
    else \
      composer install --no-interaction --prefer-dist --no-dev --no-scripts; \
    fi

COPY . .

RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then \
      composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader; \
    else \
      composer install --no-interaction --prefer-dist --no-dev --no-scripts --optimize-autoloader; \
    fi \
    && composer dump-autoload --optimize --classmap-authoritative \
    && test -f vendor/autoload_runtime.php

RUN mkdir -p var/cache var/log public/uploads config/jwt \
    && chown -R www-data:www-data var public/uploads config/jwt

COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/sites-available/default.conf
RUN ln -sf /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default.conf \
    && rm -f /etc/nginx/sites-enabled/default \
    && sed -i 's/;clear_env = yes/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Symfony requires a .env file on disk; runtime secrets come from Railway / compose env_file.
RUN cp .env.example .env

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
