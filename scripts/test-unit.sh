#!/usr/bin/env bash
# Chạy unit test PHPUnit trong container composer.
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm -T composer install --no-interaction --no-progress </dev/null
docker compose run --rm -T --entrypoint php composer vendor/bin/phpunit "$@" </dev/null
