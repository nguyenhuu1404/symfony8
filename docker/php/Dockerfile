FROM php:8.4-fpm-alpine

# Cài đặt system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    icu-data-full \
    postgresql-dev \
    g++ \
    $PHPIZE_DEPS

# Cấu hình ext zip trỏ đúng thư viện libzip vừa cài
# (bug Alpine: file .pc của libzip-dev đôi khi không được pkg-config nhận diện đúng,
# nên set thẳng CFLAGS/LIBS để bỏ qua bước dò tìm qua pkg-config)
ENV LIBZIP_CFLAGS="-I/usr/include"
ENV LIBZIP_LIBS="-L/usr/lib -lzip"
RUN docker-php-ext-configure zip

# Cài đặt PHP extensions cần cho Symfony + Doctrine
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    opcache \
    zip

# Cài đặt Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Cài đặt Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cấu hình PHP-FPM chạy user www-data (bảo mật hơn root)
WORKDIR /var/www/html

# Copy custom php.ini nếu cần override (opcache, memory_limit...)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD ["php-fpm"]
