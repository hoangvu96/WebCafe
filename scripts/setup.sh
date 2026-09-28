#!/usr/bin/env bash
# Dựng site WordPress local. Chạy lại nhiều lần vẫn an toàn.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; source .env; set +a

wp() { docker compose run --rm -T wpcli wp "$@" </dev/null; }

docker compose up -d
echo "Đang chờ WordPress khởi động..."
until curl -fsS -o /dev/null "$WP_URL/wp-login.php"; do sleep 3; done

if ! wp core is-installed; then
  wp core install --url="$WP_URL" --title="$SITE_TITLE" \
    --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" --skip-email
fi

wp language core install vi --activate
# WooCommerce mới nhất yêu cầu WordPress >= 7.0, nhưng image wordpress:6-php8.2-apache
# ghim core ở nhánh 6.x (hiện là 6.9.4). Ghim WooCommerce 11.0.1 (bản mới nhất còn
# tương thích "Requires at least: 6.9") để cài đặt không bị lỗi.
wp plugin install woocommerce --version=11.0.1 --activate
wp plugin install kadence-blocks kadence-starter-templates --activate
wp theme install kadence
wp theme activate cafe-child
wp plugin activate cafe-core
# Một số plugin/theme có thể chưa có bản dịch tiếng Việt; thiếu bản dịch không phải lỗi.
wp language plugin install --all vi || true
wp language theme install --all vi || true

wp rewrite structure '/%postname%/'
wp eval-file /scripts/setup/options.php
wp eval-file /scripts/setup/pages.php
wp rewrite flush

echo "Xong: $WP_URL  (quản trị: $WP_URL/wp-admin)"
