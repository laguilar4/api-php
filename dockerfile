# Imagen base con PHP y Apache
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite para URLs amigables
RUN a2enmod rewrite

# Configurar ServerName para evitar el aviso AH00558
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permitir que .htaccess sobreescriba configuraciones
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configuración para CORS en Apache
RUN echo '<Directory /var/www/html>' \
    >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header set Access-Control-Allow-Origin "*"' \
    >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' \
    >> /etc/apache2/conf-enabled/cors.conf && \
    echo '    Header set Access-Control-Allow-Headers "Content-Type, Authorization"' \
    >> /etc/apache2/conf-enabled/cors.conf && \
    echo '</Directory>' \
    >> /etc/apache2/conf-enabled/cors.conf && \
    a2enmod headers

# Copiar tu proyecto a la carpeta web
COPY . /var/www/html/

# Dar permisos a la carpeta
RUN chown -R www-data:www-data /var/www/html

# Puerto expuesto
EXPOSE 80

# Comando para iniciar Apache en primer plano
CMD ["apache2-foreground"]
