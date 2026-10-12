
# Alumni Portal - PHP and Apache
FROM php:8.2-apache

RUN docker-php-ext-install mysqli
RUN a2enmod rewrite

# Configure Apache to use Railway's assigned port
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

WORKDIR /var/www/html/

RUN chown -R www-data:www-data /var/www/html/

CMD ["apache2-foreground"]
