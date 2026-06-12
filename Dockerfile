FROM php:8.2-alpine

# Install Apache dan ekstensi PostgreSQL yang dibutuhkan
RUN apk update && apk add --no-cache \
    apache2 \
    postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Buat direktori kerja untuk aplikasi
WORKDIR /var/www/localhost/htdocs

# Hapus file index.html bawaan Apache yang menampilkan "It works!"
RUN rm -f index.html

# Salin semua source code aplikasi kamu
COPY . .

# Pastikan hak akses file diatur dengan benar untuk Apache di Alpine
RUN chown -R apache:apache /var/www/localhost/htdocs

# Jalankan Apache di foreground agar kontainer tidak mati
CMD ["httpd", "-D", "FOREGROUND"]

EXPOSE 80