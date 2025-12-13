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

COPY backend/bin ./bin
COPY backend/config ./config
COPY backend/migrations ./migrations
COPY backend/public ./public
COPY backend/src ./src
COPY backend/tests ./tests

RUN composer dump-autoload --optimize

# Development FPM stage
FROM php:8.2-fpm AS development-fpm

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
    opcache \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp redis

# Enable OPcache for development (with revalidation)
RUN echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=128" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.revalidate_freq=2" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=1" >> "$PHP_INI_DIR/conf.d/opcache.ini"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 9000

CMD ["php-fpm"]

# Production stage
FROM php:8.2-fpm AS production

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    librabbitmq-dev \
    libssl-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=256" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=20000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini"

# Configure Nginx
COPY docker/backend/nginx.conf /etc/nginx/nginx.conf
COPY docker/backend/default.conf /etc/nginx/sites-available/default

# Configure Supervisor
COPY docker/backend/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /app

# Copy composer files first
COPY backend/composer.json backend/composer.lock* ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-dev

# Copy application files
COPY backend /app

# Optimize autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Set permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var && \
    chmod -R 775 var

# Copy entrypoint and make it executable (ensures permissions on mounted volumes at container start)
COPY docker/backend/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
