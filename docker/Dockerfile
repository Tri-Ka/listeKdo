# PHP 4.4.9 (CGI) + Apache, au plus proche de l'hébergement Free (PHP 4.4.3 en CGI).
# Usage local uniquement : ce dossier n'est jamais envoyé sur le FTP.
# Base Jessie : Squeeze/Wheezy plantent sous WSL2 (vsyscall absent des noyaux récents).

FROM debian:bookworm-slim AS source
RUN apt-get update && apt-get install -y --no-install-recommends curl ca-certificates bzip2 \
 && curl -fsSL https://museum.php.net/php4/php-4.4.9.tar.bz2 | tar -xj -C /usr/src

FROM debian/eol:jessie
RUN apt-get update && apt-get install -y --no-install-recommends \
      build-essential flex apache2 \
      libmysqlclient-dev libcurl4-openssl-dev libjpeg62-turbo-dev libpng12-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/* \
 # PHP 4 ne cherche les bibliothèques que dans /usr/lib (pas de multiarch)
 && for l in libmysqlclient libjpeg libpng libz; do ln -sf /usr/lib/x86_64-linux-gnu/$l.so /usr/lib/$l.so; done

COPY --from=source /usr/src/php-4.4.9 /usr/src/php
WORKDIR /usr/src/php
# curl/types.h n'existe plus depuis libcurl 7.21.7
RUN sed -i '/#include <curl\/types.h>/d' ext/curl/curl.c \
 && ./configure \
      --with-config-file-path=/usr/local/etc/php \
      --enable-force-cgi-redirect \
      --with-mysql=/usr \
      --with-gd --with-jpeg-dir=/usr --with-png-dir=/usr --with-zlib-dir=/usr --with-zlib \
      --with-curl \
      --enable-bcmath --enable-calendar --enable-ctype --enable-exif \
      --enable-sockets --enable-overload \
 && make -j"$(nproc)" && make install \
 && cd / && rm -rf /usr/src/php

# Apache tourne avec l'UID de l'utilisateur hôte : nécessaire pour safe_mode
# (contrôle de propriétaire des fichiers) et pour écrire dans uploads/.
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID site && useradd -u $UID -g $GID -M -s /bin/false site \
 && sed -i 's/^export APACHE_RUN_USER=.*/export APACHE_RUN_USER=site/; s/^export APACHE_RUN_GROUP=.*/export APACHE_RUN_GROUP=site/' /etc/apache2/envvars \
 && mkdir -p /tmp/php-sessions && chown site:site /tmp/php-sessions \
 && a2enmod actions cgi rewrite >/dev/null

COPY php.ini /usr/local/etc/php/php.ini
COPY apache-site.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["/bin/bash", "-c", "source /etc/apache2/envvars && exec apache2 -D FOREGROUND"]
