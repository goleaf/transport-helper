<?php

test('repository control files exist', function () {
    $files = [
        'AGENTS.md',
        '.codex/skills/00-global-rules.md',
        '.codex/skills/01-task-execution-loop.md',
        '.codex/skills/02-no-dto-rule.md',
        '.codex/skills/03-no-secrets-rule.md',
        '.codex/skills/04-testing-and-checks.md',
        '.codex/skills/05-git-commit-push.md',
        '.codex/skills/06-blockers-and-not-complete.md',
        'docs/current-task-template.md',
        'docs/current-task-progress-template.md',
        'docs/current-task-read-confirmation.example.md',
        'docs/repository-control-setup-notes.md',
        'docs/blockers/.gitkeep',
        'scripts/check-no-dto.sh',
        'scripts/check-no-secrets.sh',
        'scripts/check-project-docs.sh',
        'scripts/agent-guard.sh',
    ];

    foreach ($files as $file) {
        expect(base_path($file))->toBeFile();
    }
});
