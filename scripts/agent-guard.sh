#!/usr/bin/env bash
set -euo pipefail

echo "== Agent guard checks =="

if [ -f "./scripts/check-no-dto.sh" ]; then
  ./scripts/check-no-dto.sh
fi

if [ -f "./scripts/check-no-secrets.sh" ]; then
  ./scripts/check-no-secrets.sh
fi

if [ -f "./scripts/check-project-docs.sh" ]; then
  ./scripts/check-project-docs.sh
fi

if [ -f "artisan" ]; then
  php artisan test
fi

if [ -x "./vendor/bin/pint" ]; then
  ./vendor/bin/pint --test
fi

if [ -f "package.json" ]; then
  npm run build
fi

echo "== Agent guard checks passed =="
