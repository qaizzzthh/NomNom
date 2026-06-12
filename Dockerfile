FROM php:8.2-apache

# Install PostgreSQL client dev packages and PDO extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Use the production configuration template
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Increase upload limit to accommodate files up to 5MB (set to 20M for safety)
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/g' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/post_max_size = 8M/post_max_size = 20M/g' "$PHP_INI_DIR/php.ini"

# Set working directory to standard Apache document root
WORKDIR /var/www/html

# Copy application source code
COPY . .

# Pre-create upload directories and ensure proper permissions for www-data
RUN mkdir -p uploads/restaurants uploads/products uploads/avatars uploads/payments uploads/reviews && \
    chown -R www-data:www-data /var/www/html

# Expose port 80 (standard Apache port, Render routes traffic here)
EXPOSE 80

# Run Apache in foreground
CMD ["apache2-foreground"]