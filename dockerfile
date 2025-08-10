FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache para permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.json y composer.lock primero (para aprovechar la cache)
COPY composer.json composer.lock* ./

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

# Copiar el resto de archivos del proyecto
COPY . .

# Dar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80

CMD ["apache2-foreground"]
