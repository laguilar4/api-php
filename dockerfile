# Imagen base
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar herramientas
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Habilitar módulos de Apache
RUN a2enmod rewrite headers

# Configurar ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configuración CORS
RUN echo '<IfModule mod_headers.c>' > /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Origin "*"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '</IfModule>' >> /etc/apache2/conf-enabled/cors.conf

# Copiar composer.json y composer.lock primero
COPY composer.json composer.lock /var/www/html/

# Instalar dependencias PHP
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Copiar el resto del proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
