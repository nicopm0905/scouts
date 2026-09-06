FROM php:8.3-fpm

WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libicu-dev \
    zlib1g-dev \
    libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies (build should be done locally before deployment)
RUN npm install

# Run migrations (optional - may fail if DB not ready, but that's ok)
RUN php artisan migrate:fresh --force --no-interaction || true

# Expose port
EXPOSE 8000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
