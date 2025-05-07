FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides for /var/www/html
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install the pdo and pdo_mysql extensions
RUN docker-php-ext-install pdo pdo_mysql && docker-php-ext-enable pdo_mysql

# Copy source code
COPY . /var/www/html/

# (Optional) Set the document root if your main files are in a subdirectory like 'public'
# ENV APACHE_DOCUMENT_ROOT /var/www/html/public
# <VirtualHost *:80>
#     ServerName your_app_domain.railway.app
#     DocumentRoot /var/www/html/public
#     <Directory /var/www/html/public>
#         AllowOverride All
#         Require all granted
#     </Directory>
#     ErrorLog ${APACHE_LOG_DIR}/error.log
#     CustomLog ${APACHE_LOG_DIR}/access.log combined
# </VirtualHost>
