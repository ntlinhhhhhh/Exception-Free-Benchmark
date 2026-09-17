FROM php:8.0-fpm

# Install PDO, PDO MySQL, and mysqli extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
