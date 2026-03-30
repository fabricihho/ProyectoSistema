FROM php:8.2-apache

# Copiar archivos
COPY . /var/www/html/

# Activar mod_rewrite (importante para rutas)
RUN a2enmod rewrite

# Cambiar DocumentRoot a /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Permitir .htaccess
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>' >> /etc/apache2/apache2.conf
