FROM php:7.4-fpm-alpine

COPY --from=wizbii/caddy /caddy /usr/local/bin/caddy

RUN apk add --no-cache autoconf openssl-dev g++ make pcre-dev icu-dev zlib-dev libzip-dev && \
    docker-php-ext-install bcmath intl opcache zip sockets && \
    apk del --purge autoconf g++ make && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /home/root

COPY . .

ENV PORT=80

RUN caddy -validate -conf=docker/Caddyfile && \
    cp docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

EXPOSE 80

CMD ["caddy", "--conf", "docker/Caddyfile", "--log", "stdout"]