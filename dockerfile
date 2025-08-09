FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache para permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar el código
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80
