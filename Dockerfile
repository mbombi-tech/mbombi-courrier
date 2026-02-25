FROM php:8.3-apache

# Installer extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copier le projet dans le serveur Apache
COPY . /var/www/html/

# Donner les permissions
RUN chown -R www-data:www-data /var/www/html
