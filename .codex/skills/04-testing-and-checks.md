# Testing And Checks

Required checks for every task:

```bash
./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan test
```

If available:

```bash
./vendor/bin/pint
npm run build
```

Tests must not:

* call real AI providers;
* call real email providers;
* call real Google Sheets;
* call real ERP APIs;
* call carrier APIs;
* depend on production data;
* require real credentials.

Use fake/manual providers in tests.

Final response must include exact command results.
