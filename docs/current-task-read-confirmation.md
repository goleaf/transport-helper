# Current Task Read Confirmation

## Source

`docs/current-task.md`

## Total headings found

12

## Section titles

- Current Task
- Task title
- Non-negotiable rules
- Scope
- Out of scope
- Files likely affected
- Required implementation
- Required tests
- Required documentation
- Acceptance criteria
- Required commands
- Commit message

## Understanding

- Current Task: This file is the source of truth for the current repository task.
- Task title: The task is to formalize a reusable execution protocol around current task files, progress tracking, guard checks, and CI.
- Non-negotiable rules: The task must be implemented completely without DTOs, `app/Data`, real external calls, or finishing before checks pass.
- Scope: The implementation is limited to agent rules, progress/read-confirmation docs, guard script, CI, and docs checks.
- Out of scope: No business workflow, calculation formula, external integration, or dependency upgrade work is included.
- Files likely affected: The expected changes are docs, scripts, AGENTS rules, and GitHub Actions workflow files.
- Required implementation: The repository must enforce reading current tasks, maintaining progress, running `agent-guard.sh`, documenting blockers, and verifying protocol files.
- Required tests: The main proof is `./scripts/agent-guard.sh`, with underlying commands rerun directly if it fails.
- Required documentation: AGENTS, roadmap, current task, read confirmation, and progress docs must be updated.
- Acceptance criteria: The final report must include the task checklist and mark each item by evidence.
- Required commands: `./scripts/agent-guard.sh` is the single mandatory guard command.
- Commit message: The expected commit message is `chore(agent): add current task protocol guard`.

## Acceptance criteria copied

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
