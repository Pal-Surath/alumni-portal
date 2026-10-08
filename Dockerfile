# ==========================================
# ALUMNI PORTAL - DOCKERFILE
# PHP + Apache
# ==========================================

FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files into Apache web directory
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

# Apache listens on port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

