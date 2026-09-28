#!/usr/bin/env bash
# Chạy E2E Playwright với site local (cần setup.sh + seed.sh đã chạy).
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; source .env; set +a
cd tests/e2e
npm ci --no-audit --no-fund
npx playwright install chromium
npx playwright test "$@"
