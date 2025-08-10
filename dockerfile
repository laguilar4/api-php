FROM php:8.2-apache

# Instalar dependencias del sistema necesarias
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    && docker-php-ext-install zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite en Apache
RUN a2enmod rewrite

# Configurar Apache para permitir .htaccess
RUN echo '<Directory /var/www/html>' \
         '\n    AllowOverride All' \
         '\n</Directory>' \
         >> /etc/apache2/apache2.conf

# Copiar composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar el proyecto al contenedor
COPY . /var/www/html

# Instalar dependencias PHP (ejecuta composer install dentro del contenedor)
WORKDIR /var/www/html
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
