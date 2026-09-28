#!/usr/bin/env bash
# Tạo dữ liệu mẫu. Chạy sau setup.sh và sau khi import mẫu Kadence (Task 14).
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm -T wpcli wp "$@" </dev/null; }

wp eval-file /scripts/seed/products.php
wp eval-file /scripts/seed/pages.php
wp eval-file /scripts/seed/menu.php
wp rewrite flush
