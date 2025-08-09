# Usamos la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Copiamos el código al directorio de Apache
COPY . /var/www/html/

# Damos permisos
RUN chown -R www-data:www-data /var/www/html

# Exponemos el puerto 80 para HTTP
EXPOSE 80

# Apache ya se inicia por defecto
