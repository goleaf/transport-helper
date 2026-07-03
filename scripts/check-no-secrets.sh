#!/usr/bin/env bash
set -euo pipefail

if git ls-files | grep -E '(^|/)\.env($|\.)|\.pem$|\.key$|id_rsa|id_ed25519' | grep -vE '(^|/)\.env\.example$' >/tmp/transport-helper-secret-files.txt; then
    echo "Secret rule violation: committed env/key files found." >&2
    cat /tmp/transport-helper-secret-files.txt >&2
    exit 1
fi

rm -f /tmp/transport-helper-secret-files.txt

if git grep -n -I -E 'AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|sk-[A-Za-z0-9_-]{20,}|-----BEGIN (RSA|DSA|EC|OPENSSH|PRIVATE) KEY-----|gh[pousr]_[A-Za-z0-9_]{36,}|xox[baprs]-[A-Za-z0-9-]+' -- ':!:*.lock' ':!:package-lock.json' ':!:composer.lock' ':!AGENTS.md' ':!.env.example'; then
    echo "Secret rule violation: common secret pattern found." >&2
    exit 1
fi

echo "No committed secrets found."
