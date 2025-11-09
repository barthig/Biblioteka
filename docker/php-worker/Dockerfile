FROM php:8.2-cli

# Install system dependencies required by PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        librabbitmq-dev \
        libssl-dev \
        pkg-config \
        autoconf \
        make \
        g++ \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apt-get purge -y --auto-remove \
        pkg-config \
        autoconf \
        make \
        g++ \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
