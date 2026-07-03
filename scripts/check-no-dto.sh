#!/usr/bin/env bash
set -euo pipefail

scan_paths=(app tests database routes resources)

if [ -d app/Data ]; then
    echo "DTO rule violation: app/Data exists." >&2
    exit 1
fi

if find "${scan_paths[@]}" \( -iname '*DTO.php' -o -iname '*Dto.php' -o -iname '*Data.php' \) -print | grep -q .; then
    echo "DTO rule violation: DTO/Data-style PHP files found." >&2
    find "${scan_paths[@]}" \( -iname '*DTO.php' -o -iname '*Dto.php' -o -iname '*Data.php' \) -print >&2
    exit 1
fi

if grep -RInE 'class[[:space:]]+[A-Za-z0-9_]*(DTO|Dto)\b' "${scan_paths[@]}" --include='*.php' >/tmp/transport-helper-dto-scan.txt; then
    echo "DTO rule violation: DTO-style PHP classes found." >&2
    cat /tmp/transport-helper-dto-scan.txt >&2
    exit 1
fi

rm -f /tmp/transport-helper-dto-scan.txt

echo "No DTO violations found."
