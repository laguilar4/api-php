# Imagen base con PHP y Apache
FROM php:8.1-apache

# Habilitar extensiones necesarias para PHP
RUN docker-php-ext-install pdo pdo_mysql

# Instalar dependencias necesarias para Composer
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.json y composer.lock primero (mejor cache de capas)
COPY composer.json composer.lock* ./

# Instalar dependencias PHP sin interacción
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copiar el resto del código fuente al contenedor
COPY . .

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 8080
EXPOSE 8080

# Iniciar Apache
CMD ["apache2-foreground"]
