# Current Task

## Task

Make the repository's agent execution rules enforceable instead of relying on prose-only instructions.

## Acceptance Criteria

- `AGENTS.md` includes mandatory behavior, work loop, required checks, and final response requirements.
- `docs/implementation-roadmap.md` exists and documents the execution guardrail direction.
- `docs/current-task.md` exists and describes the current acceptance criteria.
- `.codex/skills/00-global-project-rules.md` exists with global project constraints.
- `.codex/skills/02-no-dto-rule.md` exists with the no-DTO rule.
- `scripts/check-no-dto.sh` exists, is executable, and fails if DTO files/classes or `app/Data` exist.
- `scripts/check-no-secrets.sh` exists, is executable, and fails on committed real env/key files or common secret patterns.
- `scripts/check-project-docs.sh` exists, is executable, and fails when mandatory project docs/rules are missing.
- Required checks pass before commit.

## Non-Goals

- Do not implement a new business workflow in this task.
- Do not add DTO classes.
- Do not call real external services.
