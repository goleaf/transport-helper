# Current Task Blockers

## Task

Pilot Supplier Onboarding And UAT Workflow

## Blocker

`php artisan test --compact` is not fully green because an unrelated, untracked calculation CRUD test expects demo calculation seeding that is outside this task.

## Evidence

Command:

```bash
php artisan test --compact
```

Result:

- 638 tests run.
- 637 tests passed.
- 1 test failed.

Failing test:

```text
P\Tests\Feature\CalculationRunCrudTest::it_seeds_one_hundred_demo_calculation_runs_with_proposal_and_product_planning_relations
```

Failure:

```text
Failed asserting that actual size 0 matches expected size 100.
```

## Scope Assessment

The failing test file is untracked in the working tree and exercises calculation CRUD/demo seeding behavior. The current task implements pilot supplier onboarding and UAT workflow, so changing calculation seeding or calculation CRUD behavior would broaden the task beyond Punkt 15.

## Completed Verification For Current Scope

- `php artisan test --compact --filter=Pilot` passed.
- `php artisan test --compact --filter=NoDtoRuleTest` passed.
- `php artisan test --compact --filter=BladePresentationTest` passed.
- `./scripts/check-no-dto.sh` passed.
- `./scripts/check-no-secrets.sh` passed.
- `./scripts/check-project-docs.sh` passed.
- `php artisan migrate:fresh --seed` passed.
- `php artisan supply:pilot-onboarding-checklist --json` passed.
- `php artisan supply:health-check` passed with existing seeded data warnings.
- `php artisan supply:production-readiness` passed with warning status from the health section.
- `vendor/bin/pint --dirty --format agent` passed.
- `npm run build` passed.

## Required Follow-Up

Resolve or remove the unrelated calculation CRUD/demo seeding expectation in a calculation-specific task, then rerun the full test suite.
