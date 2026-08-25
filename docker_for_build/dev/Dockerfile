FROM ubuntu:16.04

LABEL Description="MEICAN Image" \
      Author="Leonardo Lauryel Batista dos Santos"

ARG MEICAN_VERSION \ 
   DEBIAN_FRONTEND=noninteractive \
   DEBCONF_NONINTERACTIVE_SEEN=true

ENV MYSQL_DATABASE=$MYSQL_DATABASE \
   MYSQL_USER=$MYSQL_USER \
   MYSQL_PASSWORD=$MYSQL_PASSWORD \
   MEICAN_DIR=/home/meican/meican-$MEICAN_VERSION \
   CIRCUIT_LIFECYCLE_LOGGER=$CIRCUIT_LIFECYCLE_LOGGER \
   BLOCKCHAIN_RPC_URL=$BLOCKCHAIN_RPC_URL \
   BLOCKCHAIN_CHAIN_ID=$BLOCKCHAIN_CHAIN_ID \
   BLOCKCHAIN_SIGNER_PRIVATE_KEY=$BLOCKCHAIN_SIGNER_PRIVATE_KEY
   
RUN apt-get update \
 && apt-get install -yq --no-install-recommends \
    apt-utils \
    curl \
    sudo \
    zip \
    unzip \
    # Install apache
    apache2 \
    # Install php 7.0
    php7.0 \
    php7.0-mysql \
    php7.0-mbstring \
    php7.0-curl \
    php7.0-soap \
    php7.0-xml \
    libapache2-mod-php \
    mysql-client \
    php-pear \
    php7.0-dev \
    make \
 && rm -rf /var/lib/apt/lists/*

RUN curl -fsSLk https://github.com/xdebug/xdebug/archive/refs/tags/2.7.2.tar.gz -o /tmp/xdebug-2.7.2.tgz \
 && cd /tmp \
 && tar -xzf xdebug-2.7.2.tgz \
 && cd xdebug-2.7.2 \
 && phpize \
 && ./configure --with-php-config=$(which php-config) \
 && make \
 && make install \
 && XDEBUG_SO=$(find /usr/lib/php -name "xdebug.so" | head -1) \
 && echo "zend_extension=$XDEBUG_SO" > /etc/php/7.0/mods-available/xdebug.ini \
 && echo "xdebug.remote_enable=1" >> /etc/php/7.0/mods-available/xdebug.ini \
 && echo "xdebug.remote_autostart=1" >> /etc/php/7.0/mods-available/xdebug.ini \
 && echo "xdebug.remote_host=host.docker.internal" >> /etc/php/7.0/mods-available/xdebug.ini \
 && echo "xdebug.remote_port=9000" >> /etc/php/7.0/mods-available/xdebug.ini \
 && echo "xdebug.remote_log=/tmp/xdebug.log" >> /etc/php/7.0/mods-available/xdebug.ini \
 && phpenmod -v 7.0 xdebug

RUN adduser --disabled-password --gecos '' meican \
 && usermod -aG sudo meican

RUN curl -k0 https://getcomposer.org/download/2.2.23/composer.phar -o /usr/local/bin/composer \
 && chmod +x /usr/local/bin/composer \
 && ln -s $MEICAN_DIR/web /var/www/meican \
 && a2enmod rewrite

WORKDIR $MEICAN_DIR

COPY docker_for_build/000-default.conf /etc/apache2/sites-available/

COPY docker_for_build/dev/entrypoint.sh /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
