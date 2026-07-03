#!/usr/bin/env bash
set -euo pipefail

echo "Checking for obvious committed secrets..."

FILES=$(git ls-files 2>/dev/null || find . -type f)
SUSPICIOUS=0

while IFS= read -r file; do
    case "$file" in
        .env|*.key|*.pem|*.p12|*.pfx|id_rsa|id_ed25519)
            echo "Suspicious secret-like file tracked: $file"
            SUSPICIOUS=1
            continue
            ;;
    esac

    case "$file" in
        vendor/*|node_modules/*|storage/*|bootstrap/cache/*|composer.lock|package-lock.json|scripts/check-no-secrets.sh)
            continue
            ;;
    esac

    if [ -f "$file" ]; then
        SECRET_PATTERN_MATCHES=$(grep -InE "AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|sk-[A-Za-z0-9_-]{20,}|-----BEGIN (RSA|DSA|EC|OPENSSH|PRIVATE) KEY-----|gh[pousr]_[A-Za-z0-9_]{36,}|xox[baprs]-[A-Za-z0-9-]+" "$file" 2>/dev/null | grep -vE "(your-key-here|changeme|example|null|false|true|PLACEHOLDER|placeholder|xxx|xxxx|dummy|test)" || true)
        if [ -n "$SECRET_PATTERN_MATCHES" ]; then
            echo "Suspicious secret pattern found in $file"
            SUSPICIOUS=1
        fi

        SECRET_ASSIGNMENT_MATCHES=$(grep -InE "(OPENAI_API_KEY|GOOGLE_CLIENT_SECRET|SMTP_PASSWORD|AWS_SECRET_ACCESS_KEY|PRIVATE_KEY|refresh_token|client_secret|api_key|password|secret|\\btoken\\b)[A-Za-z0-9_ -]*(=|:)[[:space:]]*['\"]?[^'\"[:space:]#]+" "$file" 2>/dev/null | grep -vE "(your-key-here|changeme|example|null|false|true|PLACEHOLDER|placeholder|xxx|xxxx|dummy|test|wJalrXUtnFEMI|APP_KEY=$|DB_PASSWORD=$|AWS_SECRET_ACCESS_KEY=$)" || true)
        if [ -n "$SECRET_ASSIGNMENT_MATCHES" ]; then
            echo "Suspicious secret assignment found in $file"
            SUSPICIOUS=1
        fi
    fi
done <<< "$FILES"

if [ "$SUSPICIOUS" -ne 0 ]; then
    echo "Potential secrets detected. Review before commit."
    exit 1
fi

echo "No obvious secrets found."
