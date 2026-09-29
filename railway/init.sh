#!/bin/bash
# Script khởi tạo WordPress lần đầu trên Railway.
# Chạy ngầm sau khi Apache đã start (gọi từ entrypoint.sh &).
set -e

LOG="[Railway Init]"
WP="wp --allow-root --path=/var/www/html"

# ─── 1. Chờ wp-config.php được tạo bởi WordPress entrypoint ──────────────
echo "$LOG Chờ wp-config.php..."
until [ -f /var/www/html/wp-config.php ]; do sleep 1; done
echo "$LOG wp-config.php sẵn sàng."

# ─── 2. Chờ MySQL sẵn sàng ────────────────────────────────────────────────
echo "$LOG Chờ MySQL tại ${MYSQL_HOST}:${MYSQL_PORT:-3306}..."
until mysqladmin ping \
    -h"${MYSQL_HOST}" \
    -P"${MYSQL_PORT:-3306}" \
    -u"${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    --silent 2>/dev/null; do
    sleep 3
done
echo "$LOG MySQL sẵn sàng."

# ─── 3. Kiểm tra đã khởi tạo chưa ────────────────────────────────────────
CURRENT_URL=$($WP option get siteurl 2>/dev/null || echo "")

if [ -n "$CURRENT_URL" ]; then
    echo "$LOG Đã khởi tạo tại: $CURRENT_URL"

    # Cập nhật URL nếu domain thay đổi (re-deploy sang domain mới)
    TARGET_URL="${WP_URL:-$CURRENT_URL}"
    if [ "$CURRENT_URL" != "$TARGET_URL" ]; then
        echo "$LOG Cập nhật URL: $CURRENT_URL → $TARGET_URL"
        $WP search-replace "$CURRENT_URL" "$TARGET_URL" \
            --all-tables --skip-columns=guid --quiet
        $WP rewrite flush
        echo "$LOG URL đã cập nhật."
    fi
    exit 0
fi

# ─── 4. Lần đầu: import snapshot ──────────────────────────────────────────
echo "$LOG Lần đầu khởi tạo - import snapshot..."

echo "$LOG Import database..."
mysql \
    -h"${MYSQL_HOST}" \
    -P"${MYSQL_PORT:-3306}" \
    -u"${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    "${MYSQL_DATABASE}" < /railway/db.sql
echo "$LOG Database imported."

# ─── 5. Cài plugins ───────────────────────────────────────────────────────
echo "$LOG Cài plugins..."
while IFS=, read -r plugin version; do
    [ -z "$plugin" ] || [ "$plugin" = "cafe-core" ] && continue
    $WP plugin install "$plugin" ${version:+--version="$version"} --force --quiet
    echo "$LOG   - $plugin ${version:+($version)}"
done < /railway/plugins.txt
$WP plugin activate woocommerce kadence-blocks kadence-starter-templates cafe-core --quiet || true

# ─── 6. Giải nén uploads ──────────────────────────────────────────────────
echo "$LOG Giải nén uploads..."
tar -C /var/www/html/wp-content -xzf /railway/uploads.tar.gz
chown -R www-data:www-data /var/www/html/wp-content
echo "$LOG Uploads xong."

# ─── 7. Cập nhật URL ──────────────────────────────────────────────────────
OLD_URL=$($WP option get siteurl 2>/dev/null || echo "http://localhost:8080")
NEW_URL="${WP_URL:-http://localhost}"
echo "$LOG Cập nhật URL: $OLD_URL → $NEW_URL"
$WP search-replace "$OLD_URL" "$NEW_URL" \
    --all-tables --skip-columns=guid --quiet

# ─── 8. Đặt mật khẩu từ biến môi trường ─────────────────────────────────
if [ -n "${WP_ADMIN_PASSWORD:-}" ]; then
    $WP user update "${WP_ADMIN_USER:-admin}" \
        --user_pass="${WP_ADMIN_PASSWORD}" --skip-email --quiet
    echo "$LOG Mật khẩu admin đã đặt."
fi
if [ -n "${STAFF_PASSWORD:-}" ]; then
    $WP user update "${STAFF_USER:-nhanvien}" \
        --user_pass="${STAFF_PASSWORD}" --skip-email --quiet 2>/dev/null || true
    echo "$LOG Mật khẩu nhân viên đã đặt."
fi

# ─── 9. Kích hoạt theme + flush ───────────────────────────────────────────
$WP theme activate cafe-child --quiet 2>/dev/null || true
$WP rewrite flush
$WP cache flush

echo "$LOG ✓ WordPress sẵn sàng tại: $NEW_URL"
echo "$LOG   Admin: $NEW_URL/wp-admin  (user: ${WP_ADMIN_USER:-admin})"
