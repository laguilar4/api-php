# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar herramientas necesarias para Composer (curl, unzip, git)
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Habilitar mod_rewrite y mod_headers para Apache
RUN a2enmod rewrite headers

# Configurar ServerName para evitar aviso AH00558
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permitir que .htaccess pueda sobreescribir configuraciones
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configuración CORS para Apache (en todos los responses)
RUN echo '<IfModule mod_headers.c>' > /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Origin "*"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"' >> /etc/apache2/conf-enabled/cors.conf && \
    echo '</IfModule>' >> /etc/apache2/conf-enabled/cors.conf

# Copiar el proyecto al contenedor
COPY . /var/www/html/

# Establecer permisos para www-data
RUN chown -R www-data:www-data /var/www/html

# Ejecutar composer install para instalar dependencias (firebase/php-jwt)
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Puerto expuesto por Apache
EXPOSE 80

# Comando para iniciar Apache en primer plano
CMD ["apache2-foreground"]
