<?php

test('architecture bootstrap docs exist', function () {
    $requiredDocs = [
        'AGENTS.md',
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

test('architecture docs include required bootstrap guardrails', function () {
    $calculationEngine = file_get_contents(base_path('docs/calculation-engine.md')) ?: '';
    $emailAiBoundary = file_get_contents(base_path('docs/email-ai-boundary.md')) ?: '';
    $decisionLog = file_get_contents(base_path('docs/decision-log.md')) ?: '';

    expect($calculationEngine)
        ->toContain('raw_need = 150')
        ->toContain('recommended_quantity = 156')
        ->and($emailAiBoundary)
        ->toContain('AI Cannot')
        ->and($decisionLog)
        ->toContain('No DTO');
});
