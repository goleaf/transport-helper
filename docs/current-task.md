# Current Task

## Task title

Formalize the repository task protocol around `docs/current-task.md`, progress files, a single guard command, and CI.

## Non-negotiable rules

- Read this file from start to end.
- Implement every requirement.
- Do not create DTO.
- Do not create app/Data.
- Do not call real external services.
- Do not finish until checks pass.

## Scope

- Update permanent agent rules in `AGENTS.md`.
- Add `docs/current-task-read-confirmation.md`.
- Add `docs/current-task-progress.md`.
- Add `scripts/agent-guard.sh`.
- Add GitHub Actions CI.
- Strengthen project docs checks so the task protocol is executable.

## Out of scope

- No business workflow changes.
- No changes to deterministic calculation formulas.
- No external API integration.
- No dependency upgrades unless required by existing lock files.

## Files likely affected

- `AGENTS.md`
- `docs/current-task.md`
- `docs/current-task-read-confirmation.md`
- `docs/current-task-progress.md`
- `docs/implementation-roadmap.md`
- `scripts/agent-guard.sh`
- `scripts/check-project-docs.sh`
- `.github/workflows/tests.yml`

## Required implementation

- `AGENTS.md` must require reading `docs/current-task.md` completely and writing `docs/current-task-progress.md`.
- `AGENTS.md` must require `./scripts/agent-guard.sh` before finishing.
- `AGENTS.md` must define blocker handling through `docs/blockers/current-task-blockers.md`.
- `docs/current-task-read-confirmation.md` must list every heading from this file and summarize each section.
- `docs/current-task-progress.md` must track requirements, checks, blockers, commit, and push state.
- `scripts/agent-guard.sh` must run the existing project checks, `php artisan test`, Pint test mode when available, and `npm run build` when `package.json` exists.
- `.github/workflows/tests.yml` must run project checks in CI.
- `scripts/check-project-docs.sh` must verify the protocol files, guard script, and CI workflow exist.

## Required tests

- Run `./scripts/agent-guard.sh`.
- Run individual required checks if the guard fails and rerun the guard after fixes.

## Required documentation

- Update `AGENTS.md`.
- Update `docs/implementation-roadmap.md`.
- Keep this task file as the source of truth for the current implementation stage.
- Update `docs/current-task-read-confirmation.md`.
- Update `docs/current-task-progress.md`.

## Acceptance criteria

- [ ] AGENTS.md read
- [ ] current task read from start to end
- [ ] implementation plan written
- [ ] code implemented
- [ ] tests added
- [ ] no DTO
- [ ] no app/Data
- [ ] no secrets
- [ ] no real external calls
- [ ] php artisan test passed
- [ ] check-no-dto passed
- [ ] check-no-secrets passed
- [ ] docs updated
- [ ] git status reviewed
- [ ] commit created
- [ ] push attempted

## Required commands

```bash
./scripts/agent-guard.sh
```

If the guard fails, run the failing underlying command directly, fix the issue, and rerun `./scripts/agent-guard.sh`.

## Commit message

```text
chore(agent): add current task protocol guard
```
