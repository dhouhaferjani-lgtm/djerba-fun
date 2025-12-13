# Go Adventure - Queue Worker Dockerfile
FROM php:8.4-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    linux-headers \
    oniguruma-dev \
    libzip-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        pcntl \
        sockets \
        mbstring \
        zip \
        bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN if [ -f composer.json ]; then composer install --no-scripts; fi

CMD ["php", "artisan", "queue:work", "--sleep=3", "--tries=3"]
