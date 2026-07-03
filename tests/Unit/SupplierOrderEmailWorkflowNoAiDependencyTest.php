<?php

use Tests\TestCase;

uses(TestCase::class);

it('keeps supplier order export and email workflow independent from AI services', function () {
    $files = [
        app_path('Services/Supply/SupplierOrders/SupplierOrderExportService.php'),
        app_path('Services/Supply/SupplierOrders/SupplierOrderEmailDraftService.php'),
        app_path('Services/Supply/SupplierOrders/SupplierOrderEmailApprovalService.php'),
        app_path('Services/Supply/SupplierOrders/SupplierOrderSendService.php'),
        app_path('Services/Export/SupplierOrders/CsvSupplierOrderExporter.php'),
        app_path('Services/Export/SupplierOrders/JsonSupplierOrderExporter.php'),
        app_path('Services/Export/SupplierOrders/ExcelCsvSupplierOrderExporter.php'),
    ];

    $forbidden = [
        'App\\Contracts\\AI',
        'App\\Services\\AI',
        'OpenAI',
        'LLM',
        'AiEmailExtraction',
        'FormAutofill',
        'Http::',
        'Guzzle',
        'curl_',
    ];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        foreach ($forbidden as $needle) {
            expect($source)->not->toContain($needle, "{$file} contains forbidden dependency {$needle}");
        }
    }
});
