FROM php:8.4-fpm

WORKDIR /var/www

# --- Dependencias de sistema y extensiones PHP ---
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libicu-dev \
    zlib1g-dev \
    libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    && rm -rf /var/lib/apt/lists/*

# --- Node.js 20 (para compilar los assets con Vite) ---
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# --- Composer ---
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Dependencias PHP (capa cacheable) ---
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Dependencias Node (capa cacheable) ---
COPY package.json package-lock.json ./
RUN npm ci

# --- Código de la aplicación ---
COPY . .

# --- Autoloader optimizado + descubrimiento de paquetes ---
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi

# --- Compilar assets de frontend ---
RUN npm run build \
    && rm -rf node_modules

# --- Permisos de escritura para Laravel ---
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Arranque: migraciones + servidor ---
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
