# Git Commit And Push

Before commit:

```bash
git status
git diff --stat
```

Verify:

* no .env;
* no secrets;
* no vendor;
* no node_modules;
* no storage junk;
* no app/Data;
* no DTO classes.

Commit only when required checks pass.

Commit message should match task instruction.

After commit:

```bash
git rev-parse --short HEAD
git push
```

If push fails:

* show exact error;
* show current branch;
* show remotes;
* give manual commands.
