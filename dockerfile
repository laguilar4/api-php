# --------------------------
# Imagen base con PHP 8.2 y Apache
# --------------------------
FROM php:8.2-apache

# --------------------------
# Instalar extensiones necesarias para MySQL y PDO
# --------------------------
RUN docker-php-ext-install mysqli pdo pdo_mysql

# --------------------------
# Instalar herramientas adicionales
# - curl, unzip, git son necesarios para Composer y otras tareas
# --------------------------
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# --------------------------
# Instalar Composer globalmente
# --------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --------------------------
# Habilitar módulos de Apache necesarios
# --------------------------
RUN a2enmod rewrite headers

# --------------------------
# Configurar ServerName para evitar advertencias
# --------------------------
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# --------------------------
# Permitir uso de .htaccess
# --------------------------
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# --------------------------
# Configuración CORS para permitir peticiones desde cualquier origen
# --------------------------
RUN echo '<IfModule mod_headers.c>' > /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Origin "*"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '</IfModule>' >> /etc/apache2/conf-enabled/cors.conf

# --------------------------
# Copiar composer.json y composer.lock primero
# Esto permite usar la caché de Docker si composer.json no cambia
# --------------------------
COPY composer.json composer.lock /var/www/html/

# --------------------------
# Instalar dependencias PHP con Composer
# Esto crea la carpeta vendor/ y el autoload.php
# --------------------------
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# --------------------------
# Copiar el resto del proyecto después de instalar dependencias
# Así no se reinstalan paquetes en cada build si no cambian
# --------------------------
COPY . /var/www/html/

# --------------------------
# Dar permisos a Apache para manejar los archivos
# --------------------------
RUN chown -R www-data:www-data /var/www/html

# --------------------------
# Exponer puerto 80 para el contenedor
# --------------------------
EXPOSE 80

# --------------------------
# Iniciar Apache en primer plano
# --------------------------
CMD ["apache2-foreground"]
