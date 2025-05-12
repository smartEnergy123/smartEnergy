FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache to listen on port 9000 instead of 80
RUN sed -i 's/Listen 80/Listen 9000/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:9000>/' /etc/apache2/sites-available/000-default.conf

# Set DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Update Apache config to reflect new DocumentRoot
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|' /etc/apache2/sites-available/000-default.conf && \
    sed -i "/<Directory \/var\/www\/html>/,/<\/Directory>/ s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql && docker-php-ext-enable pdo_mysql

# Copy application source code
COPY . /var/www/html/

# Set correct permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html

# Expose port 9000 to Railway
EXPOSE 9000

# Start Apache in the foreground
CMD ["apache2-foreground"]
