FROM php:7.2-apache

RUN apt-get update && apt-get install -y

RUN apt-get update && apt-get install -y vim libmemcached-dev zlib1g-dev ssl-cert libpng-dev libfreetype6-dev libjpeg62-turbo-dev unzip nodejs npm libicu-dev \
    && pecl install memcached-3.0.4 \
    && docker-php-ext-enable memcached \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr/include --with-png-dir=/usr/include --with-freetype-dir=/usr/include \
    && docker-php-ext-configure intl

RUN docker-php-ext-install -j$(nproc) opcache pdo_mysql gd exif zip calendar intl

RUN pecl install redis-3.1.2 \
    && pecl install xdebug \
    && pecl install apcu \
    && docker-php-ext-enable redis xdebug apcu

COPY ./docker/php-apache/php.ini-development /usr/local/etc/php/conf.d/php.ini
COPY ./docker/php-apache/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# On ajoute dynamiquement le port et l'IP de debug pour xdebug, car dépend de l'environnement
RUN echo "" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.client_host=${XDEBUG_REMOTE_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.client_port=${XDEBUG_REMOTE_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN npm install
RUN npm install -g yarn
RUN npm install -g gulp

ARG USER_UID
ARG USER_GID
RUN usermod -u ${USER_UID} www-data
RUN groupmod -g ${USER_GID} www-data

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

WORKDIR /var/www/html

COPY ./docker/php-apache/.bashrc /var/www
RUN chown www-data:www-data -R /var/www
RUN chmod g+w -R /var/www

COPY ./docker/php-apache/000-default.conf /etc/apache2/sites-available/
COPY ./docker/php-apache/default-ssl.conf /etc/apache2/sites-available/

RUN a2enmod deflate
RUN a2enmod headers
RUN a2enmod expires
RUN a2enmod rewrite
RUN a2enmod ssl

RUN a2ensite default-ssl