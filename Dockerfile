# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos las dependencias necesarias para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql

# Habilitamos el módulo rewrite de Apache (útil para URLs limpias)
RUN a2enmod rewrite

# Copiamos todo el contenido de la carpeta src a la raíz del servidor web
COPY src/ /var/www/html/

# Ajustamos los permisos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html

# Exponemos el puerto 80
EXPOSE 80
