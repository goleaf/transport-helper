#!/usr/bin/env bash
set -euo pipefail

echo "Checking forbidden DTO usage..."

if [ -d "app/Data" ]; then
  echo "Forbidden directory exists: app/Data"
  exit 1
fi

FOUND_DTO_FILES=$(find app -type f \( -iname "*DTO.php" -o -iname "*Dto.php" -o -iname "*Data.php" \) 2>/dev/null || true)

if [ -n "$FOUND_DTO_FILES" ]; then
  echo "Forbidden DTO/Data-like files found:"
  echo "$FOUND_DTO_FILES"
  exit 1
fi

if grep -R "Spatie\\\\LaravelData\\|DataTransferObject\\|class .*DTO\\|class .*Dto" app 2>/dev/null; then
  echo "Forbidden DTO/Data references found."
  exit 1
fi

echo "No forbidden DTO usage found."
