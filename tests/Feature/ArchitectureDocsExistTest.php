<?php

test('architecture bootstrap docs exist', function () {
    $requiredDocs = [
        'docs/architecture.md',
        'docs/domain-model.md',
        'docs/workflow-map.md',
        'docs/decision-log.md',
        'docs/calculation-engine.md',
        'docs/email-ai-boundary.md',
        'docs/email-form-autofill.md',
        'docs/import-export-adapters.md',
        'docs/status-machines.md',
        'docs/audit-and-security.md',
        'docs/backup-plan.md',
        'docs/implementation-roadmap.md',
        'docs/next-codex-prompts.md',
        'docs/repository-architecture-bootstrap-notes.md',
    ];

    foreach ($requiredDocs as $doc) {
        expect(base_path($doc))->toBeFile();
    }
});

test('architecture docs keep core guardrails visible', function () {
    $combinedDocs = collect([
        'docs/architecture.md',
        'docs/domain-model.md',
        'docs/workflow-map.md',
        'docs/calculation-engine.md',
        'docs/email-ai-boundary.md',
        'docs/audit-and-security.md',
        'docs/repository-architecture-bootstrap-notes.md',
    ])->map(fn (string $doc): string => file_get_contents(base_path($doc)) ?: '')
        ->implode("\n");

    expect($combinedDocs)
        ->toContain('Laravel')
        ->toContain('AI')
        ->toContain('deterministic')
        ->toContain('DTO')
        ->toContain('human')
        ->toContain('audit')
        ->toContain('adapter');
});
