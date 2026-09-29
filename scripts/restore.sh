#!/usr/bin/env bash
# Dựng lại site từ snapshot/. Chạy sau setup.sh trên máy mới.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; source .env; set +a

wp() { docker compose run --rm -T wpcli wp "$@" </dev/null; }

for f in snapshot/db.sql snapshot/uploads.tar.gz snapshot/plugins.txt; do
  test -f "$f" || { echo "Thiếu $f"; exit 1; }
done

docker compose up -d
echo "Đang chờ MySQL sẵn sàng..."
until docker compose exec -T db sh -c 'mysqladmin ping -uroot -p"$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1; do
  sleep 3
done

# Cài trước các plugin mà mẫu đã thêm (đúng phiên bản), để khi import DB chúng vẫn ở trạng thái active.
# Mỗi dòng plugins.txt: tên,phiên bản. cafe-core được mount từ repo nên bỏ qua.
while IFS=, read -r plugin version; do
  [ -z "$plugin" ] || [ "$plugin" = "cafe-core" ] && continue
  wp plugin install "$plugin" ${version:+--version="$version"} --force
done < snapshot/plugins.txt

# `wp db import` (mariadb trong image wpcli) không đăng nhập được vào MySQL 8
# (caching_sha2_password), nên nạp trực tiếp bằng mysql trong container db.
docker compose exec -T db sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < snapshot/db.sql

old_url="$(wp option get siteurl)"
if [ "$old_url" != "$WP_URL" ]; then
  wp search-replace "$old_url" "$WP_URL" --all-tables --skip-columns=guid
fi

# Uploads trong snapshot không có thumbnail, sinh lại theo cấu hình kích thước hiện tại.
docker compose run --rm -T --entrypoint tar wpcli -C /var/www/html/wp-content -xzf /snapshot/uploads.tar.gz </dev/null
wp media regenerate --yes

# Dump không chứa hash mật khẩu: đặt mật khẩu từ .env (tạo tài khoản nếu chưa có).
ensure_user() { # login password role email display_name
  if wp user get "$1" --field=ID >/dev/null 2>&1; then
    wp user update "$1" --user_pass="$2" --skip-email
    wp user set-role "$1" "$3"
  else
    wp user create "$1" "$4" --role="$3" --user_pass="$2" --display_name="$5"
  fi
}
ensure_user "$WP_ADMIN_USER" "$WP_ADMIN_PASSWORD" administrator "$WP_ADMIN_EMAIL" "$WP_ADMIN_USER"
ensure_user "$STAFF_USER" "$STAFF_PASSWORD" cafe_staff "$STAFF_USER@example.test" "Nhân viên bán hàng"

# Snapshot cũ có thể chưa có nút chọn ngôn ngữ và bản tiếng Anh (chỉ điền phần còn thiếu).
wp eval-file /scripts/setup/language-switcher.php
wp eval-file /scripts/setup/translations-en.php

wp rewrite flush
wp cache flush
echo "Đã khôi phục: $WP_URL"
