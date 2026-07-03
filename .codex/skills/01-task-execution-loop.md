# Task Execution Loop

For every task:

1. Read AGENTS.md.
2. Read docs/current-task.md from first line to last line.
3. Create docs/current-task-read-confirmation.md.
4. Copy all headings from docs/current-task.md into read confirmation.
5. Create docs/current-task-progress.md.
6. Convert acceptance criteria into checklist.
7. Implement one block at a time.
8. Update progress after every major block.
9. Run tests and scripts.
10. Fix failures.
11. Rerun failed checks.
12. Repeat until all checks pass.

Failure loop:

* Try to fix each failing check.
* Rerun the exact failed command.
* If the same failure remains after 5 attempts, document blocker.
* Do not commit broken work unless explicitly instructed.

Do not finish while checklist has unchecked required items.
