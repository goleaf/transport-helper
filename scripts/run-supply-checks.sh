#!/usr/bin/env bash
set -euo pipefail

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh

php artisan supply:health-check
php artisan supply:permissions-audit
php artisan supply:audit-coverage
php artisan supply:backup-verify
php artisan supply:ai-boundary-audit
php artisan supply:production-readiness

php artisan test
