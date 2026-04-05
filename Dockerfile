FROM php:8.2-apache

# 1. Habilitar el mod_rewrite de Apache para URLs amigables
RUN a2enmod rewrite

# 2. Instalar librerías del sistema necesarias para instalar las extensiones de PHP (zip y gd para phpoffice)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 3. Configurar e instalar las extensiones de PHP requeridas para MySQL y PhpSpreadsheet
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd zip

# 4. Copiar los archivos físicos al servidor
COPY . /var/www/html/

# 5. Dar permisos adecuados a los archivos
RUN chown -R www-data:www-data /var/www/html

# 6. Cambiar DocumentRoot hacia la subcarpeta /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 7. Activar la lectura de los .htaccess en la carpeta /public
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# 8. Render requiere que la aplicación escuche en el puerto dinámico asignado por la variable $PORT
ENV PORT=80
RUN sed -s -i -e "s/80/\${PORT}/" /etc/apache2/ports.conf
RUN sed -s -i -e "s/80/\${PORT}/" /etc/apache2/sites-available/000-default.conf

EXPOSE 80
