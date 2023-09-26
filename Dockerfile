###################################################################################
# PHP-FPM                                                                         #
###################################################################################
FROM php:8.1-fpm-alpine AS php-fpm
LABEL stage=php-fpm

ARG uid
ARG gid
ARG WP_CLI_VERSION=2.7.1
ARG WP_SCRIPTS_VERSION=0.22
ARG IPE_GD_WITHOUTAVIF=1

COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY ./docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# supported extensions: https://github.com/mlocati/docker-php-extension-installer
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions gd opcache imagick bcmath gettext intl mysqli pdo_mysql zip soap

RUN apk update && apk add --no-cache mysql-client bash bats

###################################################################################
# PHP-FPM for local development                                                   #
###################################################################################
FROM php-fpm as php-fpm-dev

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
RUN install-php-extensions xdebug-^3

USER www-data
