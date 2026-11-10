# Imagen base fijada a Debian bookworm para compilaciones reproducibles.
FROM php:8.2-apache-bookworm

# Solo se compila pdo_pgsql; las cabeceras de desarrollo se purgan en la misma capa
# y se conserva únicamente la biblioteca de ejecución libpq5.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libpq5 \
    && docker-php-ext-install pdo_pgsql \
    && apt-get purge -y --auto-remove libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# El prefijo zz- hace que se cargue después de conf-enabled/security.conf de Debian,
# de modo que ServerTokens Prod y ServerSignature Off sí se aplican.
COPY docker/php/security.conf /etc/apache2/conf-available/zz-salud-security.conf
COPY docker/php/entrypoint.sh /usr/local/bin/salud-entrypoint
RUN a2enmod headers \
    && a2enconf zz-salud-security \
    && chmod +x /usr/local/bin/salud-entrypoint

ENTRYPOINT ["salud-entrypoint"]
CMD ["apache2-foreground"]
