FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Change Apache to listen on port 9000
RUN sed -i 's/Listen 80/Listen 9000/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:9000>/' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides in /var/www/html
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install PDO extensions
RUN docker-php-ext-install pdo pdo_mysql && docker-php-ext-enable pdo_mysql

# Copy source code
COPY . /var/www/html/

# Set permissions (important for Laravel, optional otherwise)
RUN chown -R www-data:www-data /var/www/html

# Expose port 9000
EXPOSE 9000

# Start Apache in foreground
CMD ["apache2-foreground"]
