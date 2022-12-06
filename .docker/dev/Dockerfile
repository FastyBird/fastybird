FROM fastybird/standard:1.0-traditional

# Docker file path
ARG SERVICE_DIR=./.docker/dev
# App folder
ARG APP_PATH=/app
# Backend url prefix configuration
ARG BACKEND_PREFIX

RUN apt-get update && apt-get install php8.1-xdebug curl && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && architecture=$(uname -m) \
    && rm -rf /etc/php/8.1/fpm/php.ini /etc/php/8.1/cli/php.ini \

# Configure server
COPY ${SERVICE_DIR}/nginx/nginx.conf                /etc/nginx/nginx.conf
COPY ${SERVICE_DIR}/supervisor/supervisord.conf     /etc/supervisor/supervisord.conf

# Modify location based on configuration
RUN sed -i -e "s#__API_PREFIX#${BACKEND_PREFIX}#" "/etc/nginx/nginx.conf"

EXPOSE 9001