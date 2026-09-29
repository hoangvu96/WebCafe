#!/bin/bash
# Script khởi tạo WordPress trên Railway.
# Chạy ngầm sau khi Apache đã start (gọi từ entrypoint.sh &).
# Luôn đảm bảo plugins được cài (container restart sẽ mất plugin files).
set -e

LOG="[Railway Init]"
WP="wp --allow-root --path=/var/www/html"
# Railway MySQL 9.4 uses self-signed TLS cert; --skip-ssl bypasses client-side validation
MYSQL_OPTS="-h${MYSQL_HOST} -P${MYSQL_PORT:-3306} -u${MYSQL_USER} -p${MYSQL_PASSWORD} --skip-ssl"

# ─── 1. Chờ wp-config.php được tạo bởi WordPress entrypoint ──────────────
echo "$LOG Chờ wp-config.php..."
until [ -f /var/www/html/wp-config.php ]; do sleep 1; done
echo "$LOG wp-config.php sẵn sàng."

# ─── 2. Chờ MySQL sẵn sàng ────────────────────────────────────────────────
echo "$LOG Chờ MySQL tại ${MYSQL_HOST}:${MYSQL_PORT:-3306}..."
ATTEMPTS=0
until mysqladmin ping $MYSQL_OPTS --connect-timeout=5 --silent 2>/dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ $((ATTEMPTS % 5)) -eq 0 ]; then
        TCP_RESULT=$(timeout 5 bash -c "</dev/tcp/${MYSQL_HOST}/${MYSQL_PORT:-3306}" 2>/dev/null && echo "TCP OK" || echo "TCP FAILED")
        PING_ERR=$(mysqladmin ping $MYSQL_OPTS --connect-timeout=3 2>&1 | sed 's/password "[^"]*"/password "***"/')
        echo "$LOG   Lần $ATTEMPTS: TCP=$TCP_RESULT | $PING_ERR"
    fi
    sleep 3
done
echo "$LOG MySQL sẵn sàng."

# ─── 3. Kiểm tra đã khởi tạo chưa ────────────────────────────────────────
CURRENT_URL=$($WP option get siteurl 2>/dev/null || echo "")

# ─── Helper: cài plugins thiếu (container restart xóa filesystem) ──────────
ensure_plugins() {
    while IFS=, read -r plugin version; do
        [ -z "$plugin" ] || [ "$plugin" = "cafe-core" ] && continue
        if ! $WP plugin is-installed "$plugin" 2>/dev/null; then
            echo "$LOG   Cài plugin thiếu: $plugin ${version:+($version)}"
            $WP plugin install "$plugin" ${version:+--version="$version"} --quiet || true
        fi
    done < /railway/plugins.txt
    $WP plugin activate woocommerce kadence-blocks kadence-starter-templates cafe-core --quiet || true
}

# ─── Helper: khôi phục uploads nếu thư mục rỗng (container restart) ────────
ensure_uploads() {
    local uploads_dir="/var/www/html/wp-content/uploads"
    # Kiểm tra có file/thư mục nào không (ngoài thư mục .htaccess hoặc rỗng hẳn)
    if [ ! -d "$uploads_dir" ] || [ -z "$(ls -A "$uploads_dir" 2>/dev/null)" ]; then
        echo "$LOG Khôi phục uploads từ snapshot..."
        tar -C /var/www/html/wp-content -xzf /railway/uploads.tar.gz
        chown -R www-data:www-data "$uploads_dir"
        echo "$LOG Uploads đã khôi phục."
    fi
}

if [ -n "$CURRENT_URL" ]; then
    echo "$LOG Đã khởi tạo tại: $CURRENT_URL"

    # Đảm bảo parent theme kadence được cài và cafe-child active
    if ! $WP theme is-installed kadence 2>/dev/null; then
        echo "$LOG Cài parent theme kadence..."
        $WP theme install kadence 2>&1 | sed "s/^/$LOG   /" || true
    fi

    # Đảm bảo plugins tồn tại trên disk (container mới sẽ không có plugin files)
    echo "$LOG Kiểm tra plugins..."
    ensure_plugins

    # Đảm bảo uploads tồn tại trên disk (container mới sẽ không có ảnh)
    ensure_uploads

    echo "$LOG Kích hoạt cafe-child..."
    $WP theme activate cafe-child 2>&1 | sed "s/^/$LOG   /" || true

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
mysql $MYSQL_OPTS "${MYSQL_DATABASE}" < /railway/db.sql
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
if ! $WP theme is-installed kadence 2>/dev/null; then
    $WP theme install kadence 2>&1 | sed "s/^/$LOG   /" || true
fi
$WP theme activate cafe-child 2>&1 | sed "s/^/$LOG   /" || true
$WP rewrite flush
$WP cache flush

echo "$LOG ✓ WordPress sẵn sàng tại: $NEW_URL"
echo "$LOG   Admin: $NEW_URL/wp-admin  (user: ${WP_ADMIN_USER:-admin})"
