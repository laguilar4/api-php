# --------------------------
# Imagen base con PHP 8.2 y Apache
# --------------------------
FROM php:8.2-apache

# --------------------------
# Instalar extensiones necesarias para MySQL y PDO
# --------------------------
RUN docker-php-ext-install mysqli pdo pdo_mysql

# --------------------------
# Instalar herramientas adicionales (curl, unzip, git)
# --------------------------
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# --------------------------
# Instalar Composer globalmente
# --------------------------
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# --------------------------
# Habilitar módulos de Apache necesarios
# --------------------------
RUN a2enmod rewrite headers

# --------------------------
# Configurar Apache
# --------------------------
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# --------------------------
# Configuración CORS
# --------------------------
RUN echo '<IfModule mod_headers.c>\n\
    Header always set Access-Control-Allow-Origin "*"\n\
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"\n\
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"\n\
</IfModule>' > /etc/apache2/conf-enabled/cors.conf

# --------------------------
# Establecer el directorio de trabajo
# --------------------------
WORKDIR /var/www/html

# --------------------------
# Copiar composer.json y composer.lock primero para cache de dependencias
# --------------------------
COPY composer.json composer.lock ./

# --------------------------
# Instalar dependencias con Composer
# --------------------------
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# --------------------------
# Copiar el resto del proyecto
# --------------------------
COPY . .

# --------------------------
# Permisos para Apache
# --------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# --------------------------
# Exponer puerto
# --------------------------
EXPOSE 80

# --------------------------
# Comando por defecto
# --------------------------
CMD ["apache2-foreground"]
