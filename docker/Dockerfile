FROM php:7.4-cli-alpine

USER root

RUN apk update && apk add --no-cache git libzip-dev icu-dev gcc make autoconf libc-dev bash tzdata linux-headers

RUN docker-php-ext-configure intl && docker-php-ext-configure zip && docker-php-ext-install zip intl

# Install Xdebug (3.1.x is the last series supporting PHP 7.4).
RUN pecl update-channels
RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug

# Copy config.
ADD docker/config/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
ADD docker/config/docker-php-memory.ini /usr/local/etc/php/conf.d/memory.ini

# Set locale.
ENV LANG pl_PL.UTF-8
ENV LC_ALL pl_PL.UTF-8

RUN cp /usr/share/zoneinfo/Europe/Warsaw /etc/localtime
RUN echo "Europe/Warsaw" > /etc/timezone

RUN apk del tzdata

RUN mkdir -p /usr/bin && wget -q -O /usr/bin/composer https://getcomposer.org/download/2.2.25/composer.phar && chmod +x /usr/bin/composer

RUN addgroup comfino
RUN adduser -S -u 1000 comfino -G comfino

USER comfino
