FROM fastybird/standard:1.0-traditional

# Docker file path
ARG SERVICE_DIR=./.docker/dev
# App folder
ARG APP_PATH=/app

RUN apt-get update && apt-get install -y wget iputils-ping php8.2-xdebug curl && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && architecture=$(uname -m) \
    && rm -rf /etc/php/8.2/fpm/php.ini /etc/php/8.2/cli/php.ini

# Configure server
COPY ${SERVICE_DIR}/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# mDNS
EXPOSE 5353
# Supervisor user interface
EXPOSE 9001
# Tuya local devices
EXPOSE 6666
EXPOSE 6667
EXPOSE 6668
# Shelly local devices
EXPOSE 5683
# HomeKit
EXPOSE 51827
# Application
EXPOSE 80
EXPOSE 8000
EXPOSE 8888
