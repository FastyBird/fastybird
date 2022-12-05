FROM fastybird/standard:1.0-traditional

RUN apt-get update && apt-get install php8.1-xdebug curl && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && architecture=$(uname -m) \
    && rm -rf /etc/php/8.1/fpm/php.ini /etc/php/8.1/cli/php.ini \
