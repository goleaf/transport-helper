#!/usr/bin/env bash
set -euo pipefail

REQUIRED_FILES=(
  "AGENTS.md"
  ".codex/skills/00-global-project-rules.md"
  ".codex/skills/01-laravel-architecture.md"
  ".codex/skills/02-no-dto-rule.md"
  ".codex/skills/03-supply-workflow.md"
  ".codex/skills/04-deterministic-calculation.md"
  ".codex/skills/05-email-ai-boundary.md"
  ".codex/skills/06-email-form-autofill.md"
  ".codex/skills/07-human-review-and-audit.md"
  ".codex/skills/08-import-export-adapters.md"
  ".codex/skills/09-transport-logistics.md"
  ".codex/skills/10-testing-rules.md"
  ".codex/skills/11-git-commit-rules.md"
  "docs/architecture.md"
  "docs/decision-log.md"
  "docs/workflow-map.md"
  "docs/calculation-engine.md"
  "docs/email-ai-boundary.md"
  "docs/email-form-autofill.md"
  "docs/status-machines.md"
  "docs/implementation-roadmap.md"
  "docs/next-codex-prompts.md"
  "docs/repository-audit.md"
)

for file in "${REQUIRED_FILES[@]}"; do
  if [ ! -f "$file" ]; then
    echo "Missing required file: $file"
    exit 1
  fi
done

echo "All required project documentation files exist."
