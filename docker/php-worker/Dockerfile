# Development stage
FROM php:8.2-cli AS development

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    librabbitmq-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY backend/composer.json backend/composer.lock* ./

RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY backend /app

RUN composer dump-autoload --optimize

# Production stage
FROM php:8.2-cli AS production

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    librabbitmq-dev \
    libssl-dev \
    pkg-config \
    autoconf \
    make \
    g++

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Clean up
RUN apt-get purge -y --auto-remove \
    pkg-config \
    autoconf \
    make \
    g++ \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first
COPY backend/composer.json backend/composer.lock* ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-dev

# Copy application files
COPY backend /app

# Optimize autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

CMD ["php", "bin/console", "messenger:consume", "async", "-vv"]

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD php bin/console messenger:stats async || exit 1
