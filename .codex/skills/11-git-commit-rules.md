# Git Commit Rules

Before every commit:
1. Check git status.
2. Review changed files.
3. Do not commit secrets.
4. Do not commit .env.
5. Do not commit real credentials.
6. Do not commit storage files unless intentionally added.
7. Run tests if possible.
8. Run formatter if configured.
9. Check no DTO classes were added.
10. Check app/Data directory does not exist.

Preferred commands:

git status
git diff --stat

If safe:

php artisan test

If Pint exists:

./vendor/bin/pint

Check DTO rule:

find app -iname "*DTO*" -o -path "app/Data"

Commit message format:
- Use concise message.
- Mention the module.
- Example:
  "Add project skills and architecture guardrails"

Push:
- Push to current branch if remote is configured.
- If push fails, report exact reason and commands for manual push.
