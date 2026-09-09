# docker/Dockerfile - PHP 8.2 + Apache with pdo_mysql enabled.
# Used by docker/docker-compose.yml's "php" service. Optional
# containerised dev environment - the team is committed to WAMP for the
# actual demo (see docs/PROJECT_BRIEF.md, Section 8.1), but this
# replaces a page of WAMP setup instructions with `docker compose up`
# for anyone who wants it.

FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html
