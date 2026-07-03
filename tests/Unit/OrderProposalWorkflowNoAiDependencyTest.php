<?php

it('keeps order proposal workflow free from ai email and form dependencies', function () {
    $root = dirname(__DIR__, 2);
    $files = [
        $root.'/app/Services/Supply/OrderProposals/OrderProposalDecisionService.php',
        $root.'/app/Services/Supply/OrderProposals/OrderProposalApprovalService.php',
        $root.'/app/Services/Supply/OrderProposals/SupplierOrderCreationService.php',
    ];

    foreach ($files as $file) {
        $source = file_get_contents($file) ?: '';

        foreach (['OpenAI', 'LLM', 'AiEmail', 'EmailIngestion', 'EmailFormAutofill', 'Http::', 'Guzzle'] as $forbidden) {
            expect($source)->not->toContain($forbidden);
        }
    }
});
