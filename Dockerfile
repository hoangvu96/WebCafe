FROM wordpress:php8.2-apache

# Install WP-CLI + mysql client
# apt-get có thể enable thêm mpm_event gây conflict → xóa trực tiếp symlink
RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
             /etc/apache2/mods-enabled/mpm_event.load \
             /etc/apache2/mods-enabled/mpm_worker.conf \
             /etc/apache2/mods-enabled/mpm_worker.load \
    && curl -sS -o /usr/local/bin/wp \
        https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

# Copy custom theme & plugin
COPY wp-content/themes/cafe-child /var/www/html/wp-content/themes/cafe-child
COPY wp-content/plugins/cafe-core  /var/www/html/wp-content/plugins/cafe-core

# Copy snapshot for first-run init
COPY snapshot/db.sql          /railway/db.sql
COPY snapshot/uploads.tar.gz  /railway/uploads.tar.gz
COPY snapshot/plugins.txt     /railway/plugins.txt

# PHP config: tăng memory và upload limit
COPY railway/php.ini /usr/local/etc/php/conf.d/cafe-custom.ini

# Copy Railway scripts
COPY railway/entrypoint.sh /railway/entrypoint.sh
COPY railway/init.sh       /railway/init.sh
RUN chmod +x /railway/entrypoint.sh /railway/init.sh

ENTRYPOINT ["/railway/entrypoint.sh"]
