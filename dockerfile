FROM php:8.2-apache

# Instalar dependencias necesarias para Composer
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip && \
    docker-php-ext-install zip

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache para permitir .htaccess
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copiar el proyecto
COPY . /var/www/html

# Dar permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar dependencias PHP
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]
