FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && (a2dismod mpm_event || true) \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

COPY . /var/www/html/

EXPOSE 80
