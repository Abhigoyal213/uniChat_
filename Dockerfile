FROM php:8.2-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80
