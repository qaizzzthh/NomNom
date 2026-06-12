FROM php:8.2-apache

# Install ekstensi PostgreSQL untuk PHP (wajib untuk PDO pgsql)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Aktifkan modul rewrite Apache (agar file .htaccess bekerja jika ada)
RUN a2enmod rewrite

# Salin semua source code ke folder web server
COPY . /var/www/html/

# Atur permission agar Apache bisa membaca file dengan benar
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80