# PHP 8 + Apache pour le serveur de la Freebox (ARM64) et pour tester ce mode en local.
# Le même code tourne en PHP 4 chez Free (docker/Dockerfile) : lib/mysql_compat.php fait le lien.
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      libjpeg62-turbo-dev libpng-dev libcurl4-openssl-dev msmtp ca-certificates \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" mysqli gd exif bcmath calendar sockets \
 && a2enmod rewrite headers >/dev/null

# Apache tourne avec l'UID du propriétaire des fichiers, pour écrire dans uploads/ et cache/.
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID site && useradd -u $UID -g $GID -M -s /bin/false site
# Lues par /etc/apache2/envvars de l'image officielle (défaut : www-data)
ENV APACHE_RUN_USER=site APACHE_RUN_GROUP=site

COPY php.ini /usr/local/etc/php/conf.d/listekdo.ini
COPY apache-site.conf /etc/apache2/sites-available/000-default.conf
# Configure msmtp (e-mails) d'après SMTP_* puis lance Apache
COPY --chmod=755 start.sh /usr/local/bin/listekdo-start
CMD ["listekdo-start"]
