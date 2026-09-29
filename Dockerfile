FROM wordpress:php8.2-apache

# Install WP-CLI + mysql client
RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
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

# Copy Railway scripts
COPY railway/entrypoint.sh /railway/entrypoint.sh
COPY railway/init.sh       /railway/init.sh
RUN chmod +x /railway/entrypoint.sh /railway/init.sh

ENTRYPOINT ["/railway/entrypoint.sh"]
