# Development stage
FROM php:8.2-cli AS development

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    librabbitmq-dev \
    libssl-dev

RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

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

COPY backend /app

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

CMD ["php", "bin/console", "messenger:consume", "async", "-vv"]
