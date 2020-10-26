ARG PHP5_VERSION
FROM php:${PHP5_VERSION}-apache

ARG DEBIAN_FRONTEND=noninteractive

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN set -ex \
    && \
    apt update \
    && \
    apt install -y --force-yes \
        apt-utils \
        curl \
        git \
        libcurl4-nss-dev \
        libicu-dev \
        libmcrypt-dev \
        libpng-dev \
        libssl-dev \
        libxml2-dev \
        mariadb-client \
        pngquant \
        sendmail \
        unzip \
        zip \
    && \
    # Install PHP extensions
    docker-php-ext-install \
        bcmath \
        curl \
        dom \
        gd \
        hash \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        session \
        xml \
        zip \
    && \
    # Install specific foodsoft deps
    apt -y --force-yes install \
        # Perl deps for antixls script
        libspreadsheet-parseexcel-perl \
        # TeXLive for PDF generation
        texlive-latex-base \
    && \
    # Enable mod_rewrite
    a2enmod rewrite

ADD /dev/web/assets/start-web.sh /opt/bin/start-web.sh
RUN set -e && \
    chmod +x /opt/bin/start-web.sh && \
    rm /etc/apache2/sites-enabled/*

CMD apache2-foreground
