###################################################################################
# PHP-FPM                                                                         #
###################################################################################
FROM php:8.0-fpm-alpine AS php-fpm
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

RUN wget -q https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar && \
  chmod 755 wp-cli-${WP_CLI_VERSION}.phar && mv wp-cli-${WP_CLI_VERSION}.phar /usr/local/bin/wp

RUN wget -q https://github.com/arvatoaws-labs/wp-scripts/archive/${WP_SCRIPTS_VERSION}.zip && \
  unzip ${WP_SCRIPTS_VERSION}.zip && \
  mv wp-scripts* /scripts && \
  rm ${WP_SCRIPTS_VERSION}.zip

RUN apk update && apk add --no-cache mysql-client bash bats

###################################################################################
# PHP-FPM for local development                                                   #
###################################################################################
FROM php-fpm as php-fpm-dev

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
RUN install-php-extensions xdebug-^3

USER www-data

###################################################################################
# Composer                                                                        #
###################################################################################
FROM composer:2 AS intermediate-composer
LABEL stage=composer

WORKDIR /var/www/html

COPY src ./

RUN composer install --ignore-platform-reqs -n && \
    rm -f auth.json

###################################################################################
# NODE                                                                            #
###################################################################################
FROM node:16 AS intermediate-node
LABEL stage=node

WORKDIR /var/www/html

COPY --from=intermediate-composer /var/www/html /var/www/html

RUN npm install && \
    npm run build-prod

###################################################################################
# APP for AWS build                                                               #
###################################################################################
FROM php-fpm AS app

RUN mkdir /app
COPY --from=intermediate-node /var/www/html /app/

WORKDIR /app

USER www-data
