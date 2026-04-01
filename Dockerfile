FROM php:8.4-fpm

# 1. Install dependencies OS
# Tambahkan libpq-dev untuk PostgreSQL support
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libicu-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) gd zip pdo_pgsql pgsql pcntl intl

# 2. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Node.js v22 (Mengikuti catatan Anda untuk Node 22, meski copy dari image 22)
# Saran: Gunakan node:22-slim agar lebih akurat dengan label "Node 22"
COPY --from=node:25 /usr/local/bin/ /usr/local/bin/
COPY --from=node:25 /usr/local/lib/node_modules/ /usr/local/lib/node_modules/

# 4. Workdir
WORKDIR /var/www

# 5. User 'server' (UID 1000)
RUN groupadd -g 1000 server \
    && useradd -u 1000 -ms /bin/bash -g server server

# 6. Copy & Permissions
COPY --chown=server:server . /var/www

# Pastikan folder storage dan cache ada sebelum chmod
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R server:server /var/www/storage /var/www/bootstrap/cache /var/www/database \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

USER server

EXPOSE 9000

CMD ["php-fpm"]