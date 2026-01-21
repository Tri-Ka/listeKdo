# Dockerfile pour PHP 4.4.9 legacy - Version SIMPLIFIÉE
# Seules les extensions réellement utilisées sont compilées
# Extensions nécessaires : mysql, curl, mbstring, zlib, openssl

FROM debian:jessie

# Utiliser les dépôts d'archives car Jessie n'est plus supporté
RUN echo "deb http://archive.debian.org/debian/ jessie main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security/ jessie/updates main" >> /etc/apt/sources.list && \
    echo "Acquire::Check-Valid-Until false;" > /etc/apt/apt.conf.d/99no-check-valid-until && \
    echo "APT::Get::AllowUnauthenticated true;" >> /etc/apt/apt.conf.d/99allow-unauth

# Installation des dépendances nécessaires
RUN apt-get update && apt-get install -y --allow-unauthenticated \
    apache2 \
    apache2-dev \
    wget \
    build-essential \
    bison \
    flex \
    re2c \
    mysql-client \
    libmysqlclient-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libssl1.0.0 \
    zlib1g-dev \
    libjpeg62-turbo-dev \
    libpng12-dev \
    libfreetype6-dev \
    libfontconfig1-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Téléchargement de PHP 4.4.9
WORKDIR /tmp
RUN wget https://museum.php.net/php4/php-4.4.9.tar.gz && \
    tar -xzf php-4.4.9.tar.gz

# Créer des liens symboliques pour les bibliothèques
RUN ln -s /usr/lib/x86_64-linux-gnu /usr/local/lib/x86_64-linux-gnu && \
    ln -s /usr/lib/x86_64-linux-gnu/libjpeg.so /usr/lib/libjpeg.so && \
    ln -s /usr/lib/x86_64-linux-gnu/libjpeg.a /usr/lib/libjpeg.a 2>/dev/null || true && \
    ln -s /usr/lib/x86_64-linux-gnu/libpng.so /usr/lib/libpng.so && \
    ln -s /usr/lib/x86_64-linux-gnu/libpng.a /usr/lib/libpng.a 2>/dev/null || true && \
    ln -s /usr/lib/x86_64-linux-gnu/libmysqlclient.so /usr/lib/libmysqlclient.so && \
    ln -s /usr/lib/x86_64-linux-gnu/libmysqlclient.a /usr/lib/libmysqlclient.a 2>/dev/null || true && \
    mkdir -p /usr/lib/mysql && \
    ln -s /usr/lib/x86_64-linux-gnu/libmysqlclient* /usr/lib/mysql/ 2>/dev/null || true

# Configuration de PHP 4.4.9
WORKDIR /tmp/php-4.4.9
RUN sed -i '/#include "http_config.h"/a #include "unixd.h"\n#ifndef unixd_config\n#define unixd_config ap_unixd_config\n#endif\n#ifndef ap_get_server_version\n#define ap_get_server_version ap_get_server_banner\n#endif' sapi/apache2handler/php_functions.c
RUN LDFLAGS="-L/usr/lib/x86_64-linux-gnu" \
    CPPFLAGS="-I/usr/include/mysql" \
    ./configure \
    --with-apxs2=/usr/bin/apxs2 \
    --with-mysql=/usr \
    --with-mysql-sock=/var/run/mysqld/mysqld.sock \
    --with-curl=/usr \
    --with-gd \
    --with-jpeg-dir=/usr \
    --with-png-dir=/usr \
    --with-zlib-dir=/usr \
    --enable-mbstring \
    --with-config-file-path=/usr/local/lib

# Compilation de PHP 4.4.9
RUN make

# Installation de PHP 4.4.9
RUN make install

# Nettoyage
WORKDIR /
RUN rm -rf /tmp/php-4.4.9*

# Configuration de PHP
RUN echo "extension_dir=/usr/local/lib/php/extensions/no-debug-non-zts-20020429" > /usr/local/lib/php.ini && \
    echo "register_globals=On" >> /usr/local/lib/php.ini && \
    echo "magic_quotes_gpc=On" >> /usr/local/lib/php.ini && \
    echo "short_open_tag=On" >> /usr/local/lib/php.ini && \
    echo "display_errors=On" >> /usr/local/lib/php.ini && \
    echo "error_reporting=E_ALL & ~E_NOTICE" >> /usr/local/lib/php.ini && \
    echo "max_execution_time=30" >> /usr/local/lib/php.ini && \
    echo "memory_limit=32M" >> /usr/local/lib/php.ini && \
    echo "upload_max_filesize=2M" >> /usr/local/lib/php.ini && \
    echo "post_max_size=2M" >> /usr/local/lib/php.ini && \
    echo "session.save_path=/tmp/sessions" >> /usr/local/lib/php.ini

# Création du dossier sessions
RUN mkdir -p /tmp/sessions && chmod 777 /tmp/sessions

# Configuration Apache pour PHP
RUN echo "LoadModule php4_module /usr/lib/apache2/modules/libphp4.so" > /etc/apache2/mods-available/php4.load && \
    echo "<FilesMatch \\.php$>" > /etc/apache2/mods-available/php4.conf && \
    echo "    SetHandler application/x-httpd-php" >> /etc/apache2/mods-available/php4.conf && \
    echo "</FilesMatch>" >> /etc/apache2/mods-available/php4.conf && \
    a2enmod php4 && \
    a2enmod rewrite

# Copier la configuration Apache personnalisée
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2ctl", "-D", "FOREGROUND"]
