FROM php:8.3-fpm

RUN apt-get clean && apt-get update

RUN apt-get install -y \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libxml2-dev \
  libwebp-dev \
  libpng-dev \
  libzip-dev \
  libonig-dev \
  libcurl4-openssl-dev \
  libpq-dev \
  && docker-php-ext-configure gd  --with-webp --with-jpeg\
  && docker-php-ext-install -j$(nproc) gd\
  && docker-php-ext-install xml dom curl mbstring intl gettext\
  && docker-php-ext-install zip\
  && docker-php-ext-install pgsql pdo_pgsql\
  && pecl bundle -d /usr/src/php/ext apcu\
  && docker-php-ext-install /usr/src/php/ext/apcu\
  # && docker-php-ext-install sqlite3\
  && docker-php-ext-install mysqli

COPY php.ini ${PHP_INI_DIR}

# Tạo user www-data nếu chưa có và đặt ownership cho source
RUN id -u www-data >/dev/null 2>&1 || useradd -m -s /bin/bash www-data
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html/
USER www-data

# Đảm bảo thư mục runtime có quyền ghi
RUN mkdir -p \
    storage/cache \
    storage/compiled-templates \
    storage/model \
    public/page-cache \
    public/media \
    public/themes \
    public/image-cache \
    plugins \
    config \
    && chown -R www-data:www-data storage public/page-cache config public/media public/themes public/image-cache plugins \
    && chmod -R 775 storage public/page-cache config public/media public/themes public/image-cache plugins \
    && chmod -R 755 public
