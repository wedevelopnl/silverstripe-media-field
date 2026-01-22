ARG ALPINE_VERSION=3.20

FROM php:8.3-cli-alpine$ALPINE_VERSION AS php-cli

RUN apk add php make perl --no-cache

WORKDIR /app

RUN apk add --no-cache libstdc++ icu-libs icu-dev \
    && docker-php-ext-install intl \
    && apk del icu-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json ./
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --prefer-dist --no-progress --no-interaction --no-plugins

COPY dev/docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

CMD ["php"]
