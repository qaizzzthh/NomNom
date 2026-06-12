FROM php:8.2-apache

# 1. Salin seluruh source code terlebih dahulu
COPY . /var/www/html/

# 2. Update sistem dan install ekstensi PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# 3. Bersihkan konflik MPM dengan memaksa mpm_prefork (Standar PHP-Apache)
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite

# 4. Amankan kembali permission folder
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80