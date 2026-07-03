<?php

use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\FormTemplate;
use App\Models\ManufacturerFormTemplateFile;
use App\Models\Product;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\Supply\ManufacturerForms\ManufacturerFormExportService;
use App\Services\Supply\ManufacturerForms\ManufacturerFormMappingService;
use App\Services\Supply\ManufacturerForms\ManufacturerFormPreviewService;
use App\Services\Supply\ManufacturerForms\ManufacturerFormTemplateUploadService;
use App\Services\Supply\ManufacturerForms\PdfManufacturerFormRendererPlaceholder;
use App\Services\Supply\ManufacturerForms\PortalManualFormInstructionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('uploads manufacturer template files privately and stores checksum', function (): void {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $template = FormTemplate::factory()->create(['format_type' => 'excel']);

    $result = app(ManufacturerFormTemplateUploadService::class)->upload(
        $template,
        UploadedFile::fake()->create('supplier-form.xlsx', 12, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ['version' => '1.0'],
        $user,
    );

    expect($result['file'])->toBeInstanceOf(ManufacturerFormTemplateFile::class)
        ->and($result['file']->checksum)->not->toBeNull()
        ->and($result['file']->stored_path)->toStartWith('manufacturer-form-templates/')
        ->and($result['file']->is_active)->toBeTrue();

    Storage::disk('local')->assertExists($result['file']->stored_path);
});

it('saves and validates manufacturer form mapping', function (): void {
    $template = FormTemplate::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $mapping = [
        'header' => ['order_number' => 'B2', 'order_date' => 'B3'],
        'items' => [
            'start_row' => 10,
            'columns' => ['sku' => 'A', 'ordered_quantity' => 'D', 'unit' => 'E'],
        ],
    ];

    $result = app(ManufacturerFormMappingService::class)->saveMapping($template, $mapping, $user);

    expect($result['valid'])->toBeTrue()
        ->and($template->fresh()->renderer_config_json['manufacturer_mapping'])->toBe($mapping);
});

it('rejects mapping without item start row', function (): void {
    $template = FormTemplate::factory()->create();

    expect(fn () => app(ManufacturerFormMappingService::class)->validateMapping($template, [
        'header' => ['order_number' => 'B2'],
        'items' => ['columns' => ['sku' => 'A']],
    ]))->toThrow(ValidationException::class);
});

it('previews mapped supplier order data', function (): void {
    $template = FormTemplate::factory()->create([
        'renderer_config_json' => [
            'manufacturer_mapping' => [
                'header' => ['order_number' => 'B2'],
                'items' => ['start_row' => 10, 'columns' => ['sku' => 'A', 'ordered_quantity' => 'D']],
            ],
        ],
    ]);
    $order = SupplierOrder::factory()->create(['order_number' => 'PO-PREVIEW']);
    $product = Product::factory()->create(['sku' => 'SKU-1001']);
    SupplierOrderItem::factory()->create([
        'supplier_order_id' => $order->id,
        'product_id' => $product->id,
        'ordered_quantity' => 156,
    ]);

    $preview = app(ManufacturerFormPreviewService::class)->preview($template, $order);

    expect($preview['header']['order_number'])->toBe('PO-PREVIEW')
        ->and($preview['items'][0]['sku'])->toBe('SKU-1001')
        ->and($preview['items'][0]['ordered_quantity'])->toBe('156.0000');
});

it('uses placeholders for unsupported pdf renderer and portal instructions for manual portals', function (): void {
    $order = SupplierOrder::factory()->create(['order_number' => 'PO-PORTAL']);
    $template = FormTemplate::factory()->create([
        'renderer_config_json' => [
            'portal_url' => 'https://supplier.example.test/orders',
            'manufacturer_mapping' => [
                'header' => ['order_number' => 'B2'],
                'items' => ['start_row' => 10, 'columns' => ['sku' => 'A', 'ordered_quantity' => 'D']],
            ],
        ],
    ]);

    expect(fn () => app(PdfManufacturerFormRendererPlaceholder::class)->render($order, $template, []))
        ->toThrow(NotConfiguredYetException::class);

    $instructions = app(PortalManualFormInstructionService::class)->instructions($order, $template);

    expect($instructions['portal_url'])->toBe('https://supplier.example.test/orders')
        ->and($instructions['checklist'])->toContain('Log in to the supplier portal manually.');
});

it('writes audit for manufacturer form upload mapping and export attempt', function (): void {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $template = FormTemplate::factory()->create(['format_type' => 'portal_manual']);
    $order = SupplierOrder::factory()->create();

    app(ManufacturerFormTemplateUploadService::class)->upload(
        $template,
        UploadedFile::fake()->create('supplier-form.csv', 4, 'text/csv'),
        [],
        $user,
    );
    app(ManufacturerFormMappingService::class)->saveMapping($template, [
        'header' => ['order_number' => 'A1'],
        'items' => ['start_row' => 2, 'columns' => ['sku' => 'A', 'ordered_quantity' => 'B']],
    ], $user);
    app(ManufacturerFormExportService::class)->export($order, $template->fresh(), [], $user);

    expect(AuditLog::query()->pluck('event_type')->all())->toContain(
        'manufacturer_form_template_uploaded',
        'manufacturer_form_mapping_saved',
        'manufacturer_form_exported',
    );
});
