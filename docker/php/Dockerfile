FROM php:8.2-apache

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Instalar dependencias necesarias para PostgreSQL y procesamiento de imágenes (GD)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP (PDO PostgreSQL y GD para imágenes)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd

# Ajustar permisos por defecto del directorio de trabajo
RUN chown -R www-data:www-data /var/www/html

COPY docker/php/security.conf /etc/apache2/conf-available/salud-security.conf
COPY docker/php/entrypoint.sh /usr/local/bin/salud-entrypoint
RUN a2enmod headers \
    && a2enconf salud-security \
    && chmod +x /usr/local/bin/salud-entrypoint

ENTRYPOINT ["salud-entrypoint"]
CMD ["apache2-foreground"]
