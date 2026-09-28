#!/usr/bin/env bash
# Chạy unit test PHPUnit (bản PHAR) trong container composer.
# Dùng PHAR vì từ container không tải được gói từ GitHub (proxy mạng nội bộ).
set -euo pipefail
cd "$(dirname "$0")/.."
PHPUNIT=tools/phpunit.phar
if [ ! -f "$PHPUNIT" ]; then
  mkdir -p tools
  curl -fsSL -o "$PHPUNIT" https://phar.phpunit.de/phpunit-10.phar
fi
docker compose run --rm -T composer dump-autoload --no-interaction </dev/null
docker compose run --rm -T --entrypoint php composer "$PHPUNIT" "$@" </dev/null
