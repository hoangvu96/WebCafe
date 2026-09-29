#!/bin/bash
set -e

# ─── Map Railway MySQL vars → WordPress vars ───────────────────────────────
# Railway MySQL service cung cấp MYSQLHOST / MYSQLPORT / MYSQLUSER /
# MYSQLPASSWORD / MYSQLDATABASE (naming cũ) hoặc MYSQL_HOST / MYSQL_PORT ...
if [ -n "${MYSQLHOST:-}" ]; then
    export MYSQL_HOST="$MYSQLHOST"
    export MYSQL_PORT="${MYSQLPORT:-3306}"
    export MYSQL_USER="$MYSQLUSER"
    export MYSQL_PASSWORD="$MYSQLPASSWORD"
    export MYSQL_DATABASE="$MYSQLDATABASE"
fi

export WORDPRESS_DB_HOST="${MYSQL_HOST}:${MYSQL_PORT:-3306}"
export WORDPRESS_DB_USER="${MYSQL_USER}"
export WORDPRESS_DB_PASSWORD="${MYSQL_PASSWORD}"
export WORDPRESS_DB_NAME="${MYSQL_DATABASE}"

# ─── Build WP_URL từ Railway public domain nếu chưa set ───────────────────
if [ -z "${WP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export WP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

# ─── Cấu hình Apache lắng nghe trên $PORT (Railway yêu cầu) ───────────────
APACHE_PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$APACHE_PORT>/g" \
    /etc/apache2/sites-enabled/000-default.conf

# ─── Chạy init trong nền (sau khi Apache khởi động) ──────────────────────
/railway/init.sh &

# ─── Khởi động WordPress (tạo wp-config.php) + Apache ────────────────────
exec /usr/local/bin/docker-entrypoint.sh apache2-foreground
