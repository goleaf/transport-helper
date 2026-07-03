#!/usr/bin/env bash
set -euo pipefail

required_files=(
    "AGENTS.md"
    "docs/implementation-roadmap.md"
    "docs/current-task.md"
    ".codex/skills/00-global-project-rules.md"
    ".codex/skills/02-no-dto-rule.md"
)

missing=0

for file in "${required_files[@]}"; do
    if [ ! -s "$file" ]; then
        echo "Project docs check failed: missing or empty $file" >&2
        missing=1
    fi
done

required_phrases=(
    "Never create DTO classes."
    "Never create app/Data."
    "Never call real external AI, email, Google, carrier or ERP APIs in tests."
    "Commit only after checks pass."
)

for phrase in "${required_phrases[@]}"; do
    if ! grep -Fq "$phrase" AGENTS.md; then
        echo "Project docs check failed: AGENTS.md missing phrase: $phrase" >&2
        missing=1
    fi
done

if [ "$missing" -ne 0 ]; then
    exit 1
fi

echo "Project docs are present."
