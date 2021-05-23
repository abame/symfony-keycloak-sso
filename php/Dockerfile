FROM php:7.4.16-fpm-alpine

RUN apk add --update --no-cache \
    coreutils \
    make \
    curl

RUN docker-php-ext-install pdo_mysql

RUN echo "$(curl -sS https://composer.github.io/installer.sig) -" > composer-setup.php.sig \
        && curl -sS https://getcomposer.org/installer | tee composer-setup.php | sha384sum -c composer-setup.php.sig \
        && php composer-setup.php && rm composer-setup.php* \
        && chmod +x composer.phar && mv composer.phar /usr/bin/composer

COPY symfony.ini /etc/php/7.4/conf.d/
COPY symfony.ini /etc/php/7.4/cli/conf.d/
COPY symfony.pool.conf /etc/php/7.4/php-fpm.d/

CMD ["php-fpm", "-F"]

WORKDIR /var/www/html
EXPOSE 9000