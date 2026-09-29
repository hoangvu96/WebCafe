#!/usr/bin/env bash
# Xuất trạng thái site (DB + uploads + danh sách plugin) vào snapshot/.
# Dump không chứa hash mật khẩu, phiên đăng nhập, phiên giỏ hàng hay transient;
# uploads không chứa log, file import mẫu và ảnh thumbnail (restore.sh sinh lại).
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm -T wpcli wp "$@" </dev/null; }
mysqldump_db() {
  docker compose exec -T db sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --no-tablespaces "$@"' mysqldump "$@"
}

mkdir -p snapshot
prefix="$(wp db prefix)"
db_name="$(docker compose exec -T db sh -c 'printf %s "$MYSQL_DATABASE"')"

# Dọn dữ liệu nhạy cảm/tạm trước khi dump (không đổi mật khẩu thật).
wp eval 'foreach ( get_users() as $u ) { WP_Session_Tokens::get_instance( $u->ID )->destroy_all(); }'
wp transient delete --all

# `wp db export` (mariadb-dump trong image wpcli) không đọc được tài khoản
# caching_sha2_password của MySQL 8, nên dump trực tiếp bằng mysqldump trong container db.
tmp=snapshot/db.sql.tmp
trap 'rm -f "$tmp"' EXIT
mysqldump_db --ignore-table="$db_name.${prefix}users" --ignore-table="$db_name.${prefix}woocommerce_sessions" "$db_name" > "$tmp"
# Bảng users: mỗi dòng một bản ghi, xoá trắng user_pass (cột 3) và user_activation_key (cột 8).
# restore.sh đặt lại mật khẩu từ .env.
mysqldump_db --skip-extended-insert "$db_name" "${prefix}users" \
  | USERS_TABLE="${prefix}users" perl -pe '
      if ( /^INSERT INTO `\Q$ENV{USERS_TABLE}\E` VALUES \((.*)\);$/ ) {
        my @f = ( $1 =~ /(\x27(?:[^\x27\\]|\\.)*\x27|[^,]+)(?:,|$)/g );
        die "wp_users: số cột không khớp\n" unless @f == 10;
        @f[2, 7] = ( "\x27\x27", "\x27\x27" );
        $_ = "INSERT INTO `$ENV{USERS_TABLE}` VALUES (" . join( ",", @f ) . ");\n";
      }' >> "$tmp"
# Phiên giỏ hàng: chỉ lấy cấu trúc bảng.
mysqldump_db --no-data "$db_name" "${prefix}woocommerce_sessions" >> "$tmp"
if grep -q '\$wp\$2y\$\|\$P\$\|\$2y\$' "$tmp"; then
  echo "Dump vẫn còn hash mật khẩu, dừng." >&2
  exit 1
fi
mv "$tmp" snapshot/db.sql
trap - EXIT

# Mỗi dòng: tên,phiên bản
wp plugin list --status=active --fields=name,version --format=csv | tail -n +2 > snapshot/plugins.txt

docker compose run --rm -T --entrypoint tar wpcli -C /var/www/html/wp-content -czf /snapshot/uploads.tar.gz \
  --exclude='uploads/wc-logs' \
  --exclude='uploads/kadence_starter_templates' \
  --exclude='*demo-*-import-file*' \
  --exclude='*-[0-9]*x[0-9]*.*' \
  uploads </dev/null

ls -lh snapshot
