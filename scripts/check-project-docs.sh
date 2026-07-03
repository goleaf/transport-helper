#!/usr/bin/env bash
set -euo pipefail

required_files=(
    "AGENTS.md"
    "docs/implementation-roadmap.md"
    "docs/current-task.md"
    "docs/current-task-read-confirmation.md"
    "docs/current-task-progress.md"
    ".codex/skills/00-global-project-rules.md"
    ".codex/skills/02-no-dto-rule.md"
    "scripts/agent-guard.sh"
    ".github/workflows/tests.yml"
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
    "Do not finish until ./scripts/agent-guard.sh passes."
)

for phrase in "${required_phrases[@]}"; do
    if ! grep -Fq "$phrase" AGENTS.md; then
        echo "Project docs check failed: AGENTS.md missing phrase: $phrase" >&2
        missing=1
    fi
done

current_task_phrases=(
    "## Acceptance criteria"
    "- [ ] AGENTS.md read"
    "- [ ] current task read from start to end"
    "- [ ] php artisan test passed"
    "- [ ] push attempted"
)

for phrase in "${current_task_phrases[@]}"; do
    if ! grep -Fq -- "$phrase" docs/current-task.md; then
        echo "Project docs check failed: docs/current-task.md missing phrase: $phrase" >&2
        missing=1
    fi
done

progress_phrases=(
    "## Requirements checklist"
    "## Checks"
    "## Acceptance checklist"
    "## Blockers"
)

for phrase in "${progress_phrases[@]}"; do
    if ! grep -Fq -- "$phrase" docs/current-task-progress.md; then
        echo "Project docs check failed: docs/current-task-progress.md missing phrase: $phrase" >&2
        missing=1
    fi
done

if [ ! -x "scripts/agent-guard.sh" ]; then
    echo "Project docs check failed: scripts/agent-guard.sh is not executable" >&2
    missing=1
fi

if [ "$missing" -ne 0 ]; then
    exit 1
fi

echo "Project docs are present."
