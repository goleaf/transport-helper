<?php

namespace Database\Seeders;

use App\Enums\AiPromptVersion;
use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use App\Enums\CarrierQuoteStatus;
use App\Enums\EmailDirection;
use App\Enums\EmailProvider;
use App\Enums\ExportFileStatus;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormFieldType;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\HumanReviewStatus;
use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Enums\IntegrationConnectionType;
use App\Enums\LogisticsStatus;
use App\Enums\ManufacturerFormSubmissionStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\SupplierType;
use App\Enums\SupplyOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\AiSuggestion;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\HumanReview;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\IntegrationConnection;
use App\Models\LogisticsEntry;
use App\Models\LogisticsOption;
use App\Models\LogisticsRecord;
use App\Models\Manufacturer;
use App\Models\ManufacturerEmail;
use App\Models\ManufacturerFormSubmission;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\SalesHistory;
use App\Models\SavedView;
use App\Models\StockItem;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\SupplyAuditEvent;
use App\Models\SupplyOrder;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ComprehensiveProcurementDemoSeeder extends Seeder
{
    private CarbonImmutable $anchor;

    /**
     * Seed a relation-rich procurement demo graph.
     */
    public function run(): void
    {
        $this->anchor = CarbonImmutable::create(2026, 7, 3, 9, 0, 0, config('app.timezone', 'UTC'));

        $company = $this->seedCompany();
        $users = $this->seedUsers();
        $suppliers = $this->seedSuppliers($company);
        $carriers = $this->seedCarriers($company);
        $emailAccounts = $this->seedEmailAccounts($company);
        $products = $this->seedProducts($company, $suppliers);
        $templates = $this->seedFormTemplates($company, $suppliers, $carriers);
        $batches = $this->seedImportBatches($company, $users, $products);

        $this->seedSettings($company, $users);
        $this->seedPlanningData($company, $users, $suppliers, $products, $batches);
        $this->seedWorkflowData($company, $users, $suppliers, $carriers, $products, $templates, $emailAccounts);
        $this->seedLegacySupplyDataIfTablesExist($users, $products);
    }

    private function seedCompany(): Company
    {
        return Company::query()->updateOrCreate(
            ['code' => 'DEMO-LARGE'],
            [
                'name' => 'Procurement Operations Lab',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $definitions = [
            'admin' => ['Procurement Admin', 'procurement-admin@example.test', UserRole::Admin],
            'supply' => ['Supply Manager', 'supply-manager@example.test', UserRole::SupplyManager],
            'logistics' => ['Logistics Manager', 'logistics-manager@example.test', UserRole::LogisticsManager],
            'accountant' => ['Procurement Accountant', 'accountant@example.test', UserRole::Accountant],
            'viewer' => ['Supply Viewer', 'viewer@example.test', UserRole::Viewer],
        ];

        $users = [];

        foreach ($definitions as $key => [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role->value,
                ]
            );

            $roleModel = Role::query()->where('name', $role->value)->first();

            if ($roleModel !== null) {
                $user->roles()->syncWithoutDetaching([$roleModel->getKey()]);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, Supplier>
     */
    private function seedSuppliers(Company $company): array
    {
        $definitions = [
            'baltic' => ['LAB-BALTIC-PARTS', 'Baltic Parts Works', SupplierType::Manufacturer, 'en', 21],
            'nordic' => ['LAB-NORDIC-HYDRAULICS', 'Nordic Hydraulics Factory', SupplierType::Manufacturer, 'en', 28],
            'euro' => ['LAB-EURO-FILTERS', 'Euro Filters Manufacturing', SupplierType::Manufacturer, 'en', 18],
            'vilnius' => ['LAB-VILNIUS-DIST', 'Vilnius Distribution Hub', SupplierType::Distributor, 'en', 10],
            'mixed' => ['LAB-ROAD-RAIL-SUPPLY', 'Road Rail Supply Group', SupplierType::Mixed, 'en', 14],
            'carrier-supplier' => ['LAB-TRANSPORT-PARTNER', 'Transport Partner Supplier Desk', SupplierType::Carrier, 'en', 7],
        ];

        $suppliers = [];

        foreach ($definitions as $key => [$code, $name, $type, $language, $leadTime]) {
            $supplier = Supplier::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'type' => $type->value,
                    'default_language' => $language,
                    'default_currency' => 'EUR',
                    'default_lead_time_days' => $leadTime,
                    'is_active' => true,
                    'notes' => 'Comprehensive demo supplier for procurement workflow scenarios.',
                ]
            );

            foreach (['orders', 'logistics'] as $index => $contactType) {
                SupplierContact::query()->updateOrCreate(
                    [
                        'supplier_id' => $supplier->getKey(),
                        'email' => $contactType.'@'.Str::lower($code).'.example.test',
                    ],
                    [
                        'name' => $name.' '.Str::title($contactType),
                        'phone' => '+3706'.str_pad((string) ($supplier->getKey() + $index), 7, '0', STR_PAD_LEFT),
                        'role' => Str::title($contactType),
                        'receives_orders' => $contactType === 'orders',
                        'receives_transport_requests' => $contactType === 'logistics',
                        'is_active' => true,
                    ]
                );
            }

            $suppliers[$key] = $supplier;
        }

        return $suppliers;
    }

    /**
     * @return array<string, Carrier>
     */
    private function seedCarriers(Company $company): array
    {
        $definitions = [
            'road' => ['LAB-ROAD-EXPRESS', 'Road Express Logistics', 96.50],
            'rail' => ['LAB-RAIL-BRIDGE', 'Rail Bridge Cargo', 91.00],
            'sea' => ['LAB-BALTIC-SEA', 'Baltic Sea Freight', 88.25],
            'air' => ['LAB-AIR-URGENT', 'Air Urgent Cargo', 84.75],
            'local' => ['LAB-LOCAL-LTL', 'Local LTL Network', 93.00],
        ];

        $carriers = [];

        foreach ($definitions as $key => [$code, $name, $score]) {
            $carrier = Carrier::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'default_currency' => 'EUR',
                    'reliability_score' => $score,
                    'is_active' => true,
                    'notes' => 'Comprehensive demo carrier with quote and logistics history.',
                ]
            );

            foreach (['quotes', 'dispatch'] as $index => $contactType) {
                CarrierContact::query()->updateOrCreate(
                    [
                        'carrier_id' => $carrier->getKey(),
                        'email' => $contactType.'@'.Str::lower($code).'.example.test',
                    ],
                    [
                        'name' => $name.' '.Str::title($contactType),
                        'phone' => '+3707'.str_pad((string) ($carrier->getKey() + $index), 7, '0', STR_PAD_LEFT),
                        'is_active' => true,
                    ]
                );
            }

            $carriers[$key] = $carrier;
        }

        return $carriers;
    }

    /**
     * @return array<string, EmailAccount>
     */
    private function seedEmailAccounts(Company $company): array
    {
        $accounts = [
            'manual' => EmailAccount::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'email_address' => 'supply@procurement-lab.example.test',
                ],
                [
                    'name' => 'Manual procurement mailbox',
                    'provider' => EmailProvider::Manual->value,
                    'encrypted_config' => [
                        'mode' => 'manual_demo',
                        'allows_sending' => false,
                    ],
                    'is_active' => true,
                ]
            ),
            'archive' => EmailAccount::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'email_address' => 'archive@procurement-lab.example.test',
                ],
                [
                    'name' => 'Disabled archive mailbox',
                    'provider' => EmailProvider::ImapSmtp->value,
                    'encrypted_config' => [
                        'host' => 'imap.example.test',
                        'username' => 'demo-only',
                        'password' => null,
                    ],
                    'is_active' => false,
                ]
            ),
        ];

        return $accounts;
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @return array<int, Product>
     */
    private function seedProducts(Company $company, array $suppliers): array
    {
        $categories = ['Drive Train', 'Hydraulics', 'Filters', 'Electrical', 'Cabin', 'Service Parts'];
        $brands = ['BalticPro', 'NordicFlow', 'EuroGuard', 'FleetLine', 'RoadWorks'];
        $names = ['Axle Set', 'Brake Kit', 'Filter Cartridge', 'Hydraulic Pump', 'Sensor Pack', 'Cabin Harness'];
        $primarySuppliers = [$suppliers['baltic'], $suppliers['nordic'], $suppliers['euro'], $suppliers['mixed']];
        $fallbackSupplier = $suppliers['vilnius'];
        $products = [];

        for ($index = 1; $index <= 30; $index++) {
            $sku = 'LAB-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $category = $categories[($index - 1) % count($categories)];
            $brand = $brands[($index - 1) % count($brands)];
            $name = $brand.' '.$names[($index - 1) % count($names)].' '.$index;

            $product = Product::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'sku' => $sku,
                ],
                [
                    'manufacturer_sku' => 'MFG-'.$sku,
                    'name' => $name,
                    'category' => $category,
                    'brand' => $brand,
                    'unit' => $index % 5 === 0 ? 'set' : 'pcs',
                    'is_active' => $index % 29 !== 0,
                ]
            );

            $primarySupplier = $primarySuppliers[($index - 1) % count($primarySuppliers)];

            $this->seedSupplierProductRule($primarySupplier, $product, $index, true);
            $this->seedSupplierProductRule($fallbackSupplier, $product, $index, false);

            $products[] = $product;
        }

        return $products;
    }

    private function seedSupplierProductRule(Supplier $supplier, Product $product, int $index, bool $primary): void
    {
        $packMultiple = [6, 12, 24, 48][($index - 1) % 4];
        $moq = $primary ? $packMultiple * (1 + ($index % 3)) : $packMultiple;

        SupplierProductRule::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->getKey(),
                'product_id' => $product->getKey(),
            ],
            [
                'supplier_sku' => ($primary ? 'PRI-' : 'ALT-').$product->sku,
                'moq' => $moq,
                'pack_multiple' => $packMultiple,
                'pallet_quantity' => $packMultiple * 12,
                'min_transport_quantity' => $packMultiple * 8,
                'lead_time_days' => $primary ? 14 + ($index % 4) * 3 : 10 + ($index % 3) * 2,
                'safety_days' => 7 + ($index % 5),
                'safety_rule_type' => 'days',
                'transport_rule_type' => $index % 4 === 0 ? 'pallet' : 'standard',
                'order_enabled' => $index % 17 !== 0,
            ]
        );
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Carrier>  $carriers
     * @return array<string, FormTemplate>
     */
    private function seedFormTemplates(Company $company, array $suppliers, array $carriers): array
    {
        $templates = [];
        $definitions = [
            'supplier_order_v2' => [
                'Supplier Order Portal Form',
                FormTemplateContextType::SupplierOrder,
                FormTemplateFormatType::PortalManual,
                $suppliers['baltic'],
                null,
                [
                    ['order_number', 'Order Number', FormFieldType::Text, true],
                    ['sku', 'SKU', FormFieldType::Sku, true],
                    ['ordered_quantity', 'Ordered Quantity', FormFieldType::Decimal, true],
                    ['requested_ready_date', 'Requested Ready Date', FormFieldType::Date, false],
                    ['buyer_notes', 'Buyer Notes', FormFieldType::Textarea, false],
                ],
            ],
            'supplier_confirmation_v2' => [
                'Supplier Confirmation Review Form',
                FormTemplateContextType::SupplierConfirmation,
                FormTemplateFormatType::InternalHtml,
                $suppliers['baltic'],
                null,
                [
                    ['supplier_reference', 'Supplier Reference', FormFieldType::Text, false],
                    ['supplier_order_number', 'Supplier Order Number', FormFieldType::Text, true],
                    ['sku', 'SKU', FormFieldType::Sku, true],
                    ['confirmed_quantity', 'Confirmed Quantity', FormFieldType::Decimal, true],
                    ['ready_date', 'Ready Date', FormFieldType::Date, false],
                    ['expected_arrival_date', 'Expected Arrival Date', FormFieldType::Date, false],
                    ['notes', 'Notes', FormFieldType::Textarea, false],
                ],
            ],
            'quantity_mismatch_v1' => [
                'Quantity Mismatch Resolution Form',
                FormTemplateContextType::QuantityMismatch,
                FormTemplateFormatType::InternalHtml,
                $suppliers['nordic'],
                null,
                [
                    ['sku', 'SKU', FormFieldType::Sku, true],
                    ['ordered_quantity', 'Ordered Quantity', FormFieldType::Decimal, true],
                    ['confirmed_quantity', 'Confirmed Quantity', FormFieldType::Decimal, true],
                    ['discrepancy_reason', 'Discrepancy Reason', FormFieldType::Textarea, true],
                    ['requires_buyer_decision', 'Requires Buyer Decision', FormFieldType::Boolean, false],
                ],
            ],
            'ready_date_update_v1' => [
                'Ready Date Update Form',
                FormTemplateContextType::ReadyDateUpdate,
                FormTemplateFormatType::InternalHtml,
                $suppliers['euro'],
                null,
                [
                    ['supplier_order_number', 'Supplier Order Number', FormFieldType::Text, true],
                    ['old_ready_date', 'Old Ready Date', FormFieldType::Date, false],
                    ['new_ready_date', 'New Ready Date', FormFieldType::Date, true],
                    ['delay_reason', 'Delay Reason', FormFieldType::Textarea, false],
                ],
            ],
            'carrier_quote_v2' => [
                'Carrier Quote Intake Form',
                FormTemplateContextType::CarrierQuote,
                FormTemplateFormatType::InternalHtml,
                null,
                $carriers['road'],
                [
                    ['carrier_name', 'Carrier Name', FormFieldType::Text, true],
                    ['price', 'Price', FormFieldType::Decimal, true],
                    ['currency', 'Currency', FormFieldType::Currency, true],
                    ['pickup_date', 'Pickup Date', FormFieldType::Date, false],
                    ['delivery_date', 'Delivery Date', FormFieldType::Date, true],
                    ['transit_days', 'Transit Days', FormFieldType::Number, false],
                    ['conditions', 'Conditions', FormFieldType::Textarea, false],
                ],
            ],
            'logistics_update_v2' => [
                'Logistics Update Intake Form',
                FormTemplateContextType::LogisticsUpdate,
                FormTemplateFormatType::InternalHtml,
                $suppliers['mixed'],
                $carriers['local'],
                [
                    ['supplier_order_number', 'Supplier Order Number', FormFieldType::Text, true],
                    ['carrier_name', 'Carrier Name', FormFieldType::Text, false],
                    ['pickup_date', 'Pickup Date', FormFieldType::Date, false],
                    ['delivery_date', 'Delivery Date', FormFieldType::Date, false],
                    ['status', 'Status', FormFieldType::Select, true],
                    ['notes', 'Notes', FormFieldType::Textarea, false],
                ],
            ],
            'custom_email_form_v1' => [
                'Custom Supplier Email Form',
                FormTemplateContextType::CustomEmailForm,
                FormTemplateFormatType::Json,
                $suppliers['vilnius'],
                null,
                [
                    ['subject', 'Subject', FormFieldType::Text, true],
                    ['recipient_email', 'Recipient Email', FormFieldType::Email, true],
                    ['reply_body', 'Reply Body', FormFieldType::Textarea, true],
                    ['needs_follow_up', 'Needs Follow Up', FormFieldType::Boolean, false],
                ],
            ],
        ];

        foreach ($definitions as $code => [$name, $context, $format, $supplier, $carrier, $fields]) {
            $template = FormTemplate::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => $code,
                    'version' => '2',
                ],
                [
                    'name' => $name,
                    'context_type' => $context->value,
                    'supplier_id' => $supplier?->getKey(),
                    'carrier_id' => $carrier?->getKey(),
                    'format_type' => $format->value,
                    'fields_schema_json' => [
                        'fields' => array_map(fn (array $field): string => $field[0], $fields),
                    ],
                    'mapping_rules_json' => [
                        'strategy' => 'demo_seed_mapping',
                        'requires_human_application' => true,
                    ],
                    'validation_rules_json' => [
                        'human_approval_required' => true,
                    ],
                    'renderer_config_json' => [
                        'renderer' => $format === FormTemplateFormatType::Json ? 'json_export' : 'review_form',
                    ],
                    'is_active' => true,
                ]
            );

            foreach ($fields as $sortIndex => [$fieldKey, $label, $fieldType, $required]) {
                FormTemplateField::query()->updateOrCreate(
                    [
                        'form_template_id' => $template->getKey(),
                        'field_key' => $fieldKey,
                    ],
                    [
                        'label' => $label,
                        'field_type' => $fieldType->value,
                        'is_required' => $required,
                        'validation_rules_json' => $required ? ['required'] : ['nullable'],
                        'ai_extraction_hint' => 'Extract '.$label.' from supplier or carrier email body.',
                        'default_value_json' => $fieldType === FormFieldType::Currency ? ['value' => 'EUR'] : null,
                        'sort_order' => ($sortIndex + 1) * 10,
                    ]
                );
            }

            $templates[$code] = $template;
        }

        return $templates;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<int, Product>  $products
     * @return array<string, ImportBatch>
     */
    private function seedImportBatches(Company $company, array $users, array $products): array
    {
        $definitions = [
            'stock' => ['stock_snapshot', 'ERP stock export', 'erp_csv', 'stock-2026-07-03.csv', ImportBatchStatus::Completed],
            'sales' => ['sales_history', 'Sales history export', 'erp_csv', 'sales-2026-07-03.csv', ImportBatchStatus::Completed],
            'reservations' => ['reservations', 'Reservation pipeline', 'crm_csv', 'reservations-2026-07-03.csv', ImportBatchStatus::CompletedWithErrors],
            'rules' => ['supplier_rules', 'Supplier rule import', 'supplier_portal_csv', 'supplier-rules-2026-07-03.csv', ImportBatchStatus::Completed],
        ];

        $batches = [];

        foreach ($definitions as $key => [$importType, $sourceName, $adapter, $filename, $status]) {
            $failedRows = $status === ImportBatchStatus::CompletedWithErrors ? 2 : 0;
            $batch = ImportBatch::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'checksum' => hash('sha256', 'demo-'.$filename),
                ],
                [
                    'import_type' => $importType,
                    'source_type' => 'demo',
                    'source_name' => $sourceName,
                    'adapter' => $adapter,
                    'original_filename' => $filename,
                    'status' => $status->value,
                    'total_rows' => 30,
                    'successful_rows' => 30 - $failedRows,
                    'failed_rows' => $failedRows,
                    'started_by_user_id' => $users['supply']->getKey(),
                    'started_at' => $this->anchor->subHours(6),
                    'finished_at' => $this->anchor->subHours(5),
                    'error_summary' => $failedRows > 0 ? 'Two demo reservation rows need manual review.' : null,
                ]
            );

            foreach (array_slice($products, 0, 12) as $rowIndex => $product) {
                ImportRow::query()->updateOrCreate(
                    [
                        'import_batch_id' => $batch->getKey(),
                        'row_number' => $rowIndex + 1,
                    ],
                    [
                        'raw_json' => [
                            'sku' => $product->sku,
                            'source' => $filename,
                            'quantity' => 50 + $rowIndex,
                        ],
                        'normalized_json' => [
                            'product_id' => $product->getKey(),
                            'sku' => $product->sku,
                            'quantity' => 50 + $rowIndex,
                        ],
                        'status' => $key === 'reservations' && $rowIndex >= 10
                            ? ImportRowStatus::Invalid->value
                            : ImportRowStatus::Persisted->value,
                        'error_message' => $key === 'reservations' && $rowIndex >= 10
                            ? 'Demo row requires manager confirmation.'
                            : null,
                        'related_model_type' => Product::class,
                        'related_model_id' => $product->getKey(),
                    ]
                );
            }

            $batches[$key] = $batch;
        }

        return $batches;
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedSettings(Company $company, array $users): void
    {
        $settings = [
            'procurement.approval.quantity_required' => ['enabled' => true, 'threshold' => 0],
            'procurement.ai.human_review_required' => ['enabled' => true],
            'procurement.email.send_requires_approval' => ['enabled' => true],
            'logistics.carrier_selection_requires_approval' => ['enabled' => true],
            'imports.default_adapter' => ['adapter' => 'erp_csv'],
            'exports.default_currency' => ['currency' => 'EUR'],
        ];

        foreach ($settings as $key => $value) {
            AppSetting::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'key' => $key,
                ],
                [
                    'value_json' => $value,
                ]
            );
        }

        foreach ($users as $key => $user) {
            UserPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'key' => 'supply.dashboard.layout',
                ],
                [
                    'value_json' => [
                        'density' => $key === 'viewer' ? 'comfortable' : 'compact',
                        'default_company_code' => $company->code,
                    ],
                ]
            );

            SavedView::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(),
                    'key' => 'demo-'.$key.'-open-work',
                ],
                [
                    'name' => Str::title($key).' open procurement work',
                    'route_name' => 'supply.dashboard',
                    'model_type' => SupplierOrder::class,
                    'filters_json' => [
                        'company_id' => $company->getKey(),
                        'statuses' => ['needs_review', 'sent', 'delayed'],
                    ],
                    'columns_json' => ['order_number', 'supplier', 'status', 'ready_date', 'carrier'],
                    'sort_json' => ['created_at' => 'desc'],
                    'is_default' => $key === 'supply',
                    'is_shared' => in_array($key, ['admin', 'supply'], true),
                    'created_by_user_id' => $users['admin']->getKey(),
                ]
            );
        }

        foreach ([
            ['manual', 'Manual fallback integration', IntegrationConnectionType::Manual, false],
            ['erp', 'Demo ERP connector placeholder', IntegrationConnectionType::Erp, true],
            ['sheets', 'Google Sheets logistics placeholder', IntegrationConnectionType::GoogleSheets, true],
            ['email', 'Email provider placeholder', IntegrationConnectionType::Email, true],
        ] as [$provider, $name, $type, $external]) {
            IntegrationConnection::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'type' => $type->value,
                    'name' => $name,
                ],
                [
                    'provider' => $provider,
                    'environment' => 'demo',
                    'encrypted_config' => [
                        'demo' => true,
                        'configured' => false,
                    ],
                    'is_external' => $external,
                    'requires_approval' => true,
                    'status' => $external ? 'draft' : 'ready',
                    'approval_status' => $external ? 'pending' : 'approved',
                    'approved_by_user_id' => $external ? null : $users['admin']->getKey(),
                    'approved_at' => $external ? null : $this->anchor->subDays(2),
                    'last_tested_at' => $this->anchor->subDay(),
                    'last_test_status' => $external ? 'skipped' : 'passed',
                    'last_test_result_json' => [
                        'message' => $external ? 'Demo connector has no real credentials.' : 'Manual mode ready.',
                    ],
                    'is_active' => ! $external,
                    'last_sync_at' => null,
                    'notes' => 'Demo-only connection. No real credentials are stored.',
                ]
            );
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Supplier>  $suppliers
     * @param  array<int, Product>  $products
     * @param  array<string, ImportBatch>  $batches
     */
    private function seedPlanningData(Company $company, array $users, array $suppliers, array $products, array $batches): void
    {
        foreach ($products as $index => $product) {
            foreach ([21, 14, 7, 0] as $offset) {
                StockSnapshot::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'source_reference' => 'stock-'.$product->sku.'-'.$offset,
                    ],
                    [
                        'snapshot_date' => $this->anchor->subDays($offset)->toDateString(),
                        'free_stock' => max(0, 180 - ($index * 5) - $offset),
                        'total_stock' => 220 - ($index * 3),
                        'reserved_quantity' => $index % 3 === 0 ? 18 + $index : $index % 5,
                        'damaged_quantity' => $index % 11 === 0 ? 2 : 0,
                        'inactive_quantity' => $index % 13 === 0 ? 4 : 0,
                        'in_transit_quantity' => $index % 4 === 0 ? 96 : 24,
                        'source_type' => 'demo_import',
                        'import_batch_id' => $batches['stock']->getKey(),
                    ]
                );
            }

            foreach ([365, 330, 300, 270, 60, 45, 30, 15, 7, 1] as $offset) {
                SalesHistory::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'source_reference' => 'sales-'.$product->sku.'-'.$offset,
                    ],
                    [
                        'sales_date' => $this->anchor->subDays($offset)->toDateString(),
                        'quantity' => 8 + ($index % 9) + (int) floor((365 - $offset) / 60),
                        'channel' => $offset > 180 ? 'last_year_demo' : 'current_demo',
                        'customer_id' => 'DEMO-CUSTOMER-'.(($index % 6) + 1),
                        'is_promotion' => $index % 8 === 0 && $offset <= 60,
                        'is_anomaly' => $index % 10 === 0 && $offset === 15,
                        'anomaly_reason' => $index % 10 === 0 && $offset === 15 ? 'Demo spike for review queue.' : null,
                        'source_type' => 'demo_import',
                        'import_batch_id' => $batches['sales']->getKey(),
                    ]
                );
            }

            if ($index % 2 === 0) {
                Reservation::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'source_reference' => 'reservation-'.$product->sku,
                    ],
                    [
                        'quantity' => 12 + ($index % 7) * 3,
                        'project_name' => 'Fleet Retrofit '.(($index % 5) + 1),
                        'customer_name' => 'Demo Customer '.(($index % 6) + 1),
                        'manager_name' => 'Supply Manager',
                        'reserved_at' => $this->anchor->subDays($index % 6)->toDateString(),
                        'expected_usage_date' => $this->anchor->addDays(14 + $index)->toDateString(),
                        'status' => $index % 6 === 0 ? 'pending_review' : 'active',
                        'source_type' => 'demo_import',
                    ]
                );
            }
        }

        $supplierList = array_values($suppliers);

        for ($orderIndex = 1; $orderIndex <= 8; $orderIndex++) {
            $supplier = $supplierList[($orderIndex - 1) % count($supplierList)];
            $inboundOrder = InboundOrder::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'supplier_id' => $supplier->getKey(),
                    'order_number' => 'LAB-INBOUND-'.str_pad((string) $orderIndex, 3, '0', STR_PAD_LEFT),
                ],
                [
                    'supplier_order_reference' => 'SUP-IN-'.$orderIndex,
                    'status' => $orderIndex % 3 === 0 ? 'confirmed' : 'open',
                    'ordered_at' => $this->anchor->subDays(9 + $orderIndex),
                    'expected_arrival_date' => $this->anchor->addDays(8 + $orderIndex)->toDateString(),
                    'confirmed_arrival_date' => $orderIndex % 3 === 0 ? $this->anchor->addDays(9 + $orderIndex)->toDateString() : null,
                    'ready_date' => $this->anchor->addDays(4 + $orderIndex)->toDateString(),
                    'shipped_date' => $orderIndex % 4 === 0 ? $this->anchor->addDays(5 + $orderIndex)->toDateString() : null,
                    'notes' => 'Demo inbound stock pipeline item.',
                ]
            );

            foreach (array_slice($products, ($orderIndex - 1) * 3, 4) as $itemIndex => $product) {
                InboundOrderItem::query()->updateOrCreate(
                    [
                        'inbound_order_id' => $inboundOrder->getKey(),
                        'product_id' => $product->getKey(),
                    ],
                    [
                        'ordered_quantity' => 48 + ($itemIndex * 12),
                        'confirmed_quantity' => $orderIndex % 3 === 0 ? 48 + ($itemIndex * 12) : null,
                        'received_quantity' => null,
                        'damaged_quantity' => null,
                        'receiving_notes' => null,
                        'expected_arrival_date' => $this->anchor->addDays(8 + $orderIndex)->toDateString(),
                        'confirmed_arrival_date' => $orderIndex % 3 === 0 ? $this->anchor->addDays(9 + $orderIndex)->toDateString() : null,
                        'status' => $orderIndex % 3 === 0 ? 'confirmed' : 'open',
                    ]
                );
            }

            $this->audit($company, $users['supply'], 'inbound_order.seeded', $inboundOrder, [
                'source' => 'ComprehensiveProcurementDemoSeeder',
            ], $orderIndex);
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Carrier>  $carriers
     * @param  array<int, Product>  $products
     * @param  array<string, FormTemplate>  $templates
     * @param  array<string, EmailAccount>  $emailAccounts
     */
    private function seedWorkflowData(
        Company $company,
        array $users,
        array $suppliers,
        array $carriers,
        array $products,
        array $templates,
        array $emailAccounts,
    ): void {
        $workflowSuppliers = [$suppliers['baltic'], $suppliers['nordic'], $suppliers['euro'], $suppliers['vilnius']];
        $statuses = [
            SupplierOrderStatus::Draft,
            SupplierOrderStatus::AwaitingApproval,
            SupplierOrderStatus::Approved,
            SupplierOrderStatus::EmailPrepared,
            SupplierOrderStatus::Sent,
            SupplierOrderStatus::NeedsReview,
            SupplierOrderStatus::Confirmed,
            SupplierOrderStatus::Delayed,
            SupplierOrderStatus::Completed,
        ];
        $scenarioIndex = 0;
        $productIdsBySupplier = SupplierProductRule::query()
            ->whereIn('supplier_id', array_map(fn (Supplier $supplier): int => $supplier->getKey(), $workflowSuppliers))
            ->get(['supplier_id', 'product_id'])
            ->groupBy('supplier_id')
            ->map(fn ($rules): array => $rules
                ->pluck('product_id')
                ->mapWithKeys(fn (int $productId): array => [$productId => true])
                ->all())
            ->all();

        foreach ($workflowSuppliers as $supplierIndex => $supplier) {
            for ($runIndex = 1; $runIndex <= 25; $runIndex++) {
                $scenarioIndex++;
                $calculationRun = CalculationRun::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'supplier_id' => $supplier->getKey(),
                        'formula_version' => 't0-t1-t2-t3-v2-demo-'.$runIndex,
                    ],
                    [
                        'calculation_date' => $this->anchor->subDays($runIndex - 1)->toDateString(),
                        'parameters_json' => [
                            't1_days' => 30,
                            't2_days' => 60,
                            't3_days' => 90,
                            'safety_source' => 'supplier_product_rules',
                        ],
                        'status' => $scenarioIndex % 3 === 0 ? 'needs_review' : 'completed',
                        'started_by_user_id' => $users['supply']->getKey(),
                        'started_at' => $this->anchor->subDays($runIndex)->subHours(3),
                        'finished_at' => $this->anchor->subDays($runIndex)->subHours(2),
                    ]
                );

                $proposalStatus = $scenarioIndex % 4 === 0
                    ? OrderProposalStatus::NeedsReview
                    : OrderProposalStatus::Approved;

                $proposal = OrderProposal::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'calculation_run_id' => $calculationRun->getKey(),
                        'supplier_id' => $supplier->getKey(),
                    ],
                    [
                        'status' => $proposalStatus->value,
                        'total_lines' => 4,
                        'created_by_user_id' => $users['supply']->getKey(),
                        'approved_by_user_id' => $proposalStatus === OrderProposalStatus::Approved
                            ? $users['admin']->getKey()
                            : null,
                        'approved_at' => $proposalStatus === OrderProposalStatus::Approved
                            ? $this->anchor->subDays($runIndex)->subHour()
                            : null,
                        'notes' => 'Demo proposal created from deterministic replenishment inputs.',
                    ]
                );

                $proposalProducts = $this->productsForSupplier($supplier, $products, $productIdsBySupplier, ($supplierIndex + $runIndex) * 3, 4);

                foreach ($proposalProducts as $itemIndex => $product) {
                    $recommendedQuantity = 48 + ($scenarioIndex * 6) + ($itemIndex * 12);
                    $requiresReview = $itemIndex === 0 && $scenarioIndex % 3 === 0;

                    OrderProposalItem::query()->updateOrCreate(
                        [
                            'order_proposal_id' => $proposal->getKey(),
                            'product_id' => $product->getKey(),
                        ],
                        [
                            't0_date' => $this->anchor->toDateString(),
                            't1_date' => $this->anchor->addDays(30)->toDateString(),
                            't2_date' => $this->anchor->addDays(60)->toDateString(),
                            't3_date' => $this->anchor->addDays(90)->toDateString(),
                            'trend' => 1.05 + ($itemIndex / 10),
                            'need_t0_t1' => 20 + $itemIndex,
                            'stock_t1' => 12 + $itemIndex,
                            'need_t1_t2' => 24 + $itemIndex,
                            'safety_stock' => 14 + $itemIndex,
                            'inbound_until_t1' => $itemIndex % 2 === 0 ? 24 : 0,
                            'inbound_t1_t3' => 48,
                            'reserved_quantity' => $itemIndex % 2 === 0 ? 12 : 0,
                            'raw_need' => $recommendedQuantity - 6,
                            'moq_applied' => $recommendedQuantity,
                            'pack_multiple_applied' => $recommendedQuantity,
                            'pallet_quantity_applied' => $itemIndex === 3 ? 144 : null,
                            'recommended_quantity' => $recommendedQuantity,
                            'approved_quantity' => $proposalStatus === OrderProposalStatus::Approved ? $recommendedQuantity : null,
                            'user_adjusted_quantity' => $requiresReview ? $recommendedQuantity + 12 : null,
                            'adjustment_reason' => $requiresReview ? 'Demo manager adjusts quantity for customer reservation.' : null,
                            'explanation_json' => [
                                'formula' => 'deterministic_t0_t1_t2_t3',
                                'seeded' => true,
                                'supplier_code' => $supplier->code,
                            ],
                            'warnings_json' => $requiresReview ? ['reservation_pressure', 'low_stock_at_t1'] : [],
                            'requires_human_review' => $requiresReview,
                            'status' => $requiresReview
                                ? OrderProposalItemStatus::NeedsReview->value
                                : OrderProposalItemStatus::Approved->value,
                        ]
                    );
                }

                $supplierOrderStatus = $statuses[($scenarioIndex - 1) % count($statuses)];
                $supplierOrder = SupplierOrder::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'order_number' => 'LAB-PO-20260703-'.str_pad((string) $scenarioIndex, 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'supplier_id' => $supplier->getKey(),
                        'order_proposal_id' => $proposal->getKey(),
                        'status' => $supplierOrderStatus->value,
                        'order_date' => $this->anchor->subDays(2)->toDateString(),
                        'approved_by_user_id' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::Approved,
                            SupplierOrderStatus::EmailPrepared,
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $users['admin']->getKey() : null,
                        'approved_at' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::Approved,
                            SupplierOrderStatus::EmailPrepared,
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $this->anchor->subDays(2)->addHour() : null,
                        'sent_by_user_id' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $users['supply']->getKey() : null,
                        'sent_at' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $this->anchor->subDays(2)->addHours(2) : null,
                        'email_subject' => 'Purchase order LAB-PO-20260703-'.str_pad((string) $scenarioIndex, 3, '0', STR_PAD_LEFT),
                        'email_body' => 'Demo purchase order email body. Sending still requires human approval in production workflow.',
                        'email_approved_at' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::EmailPrepared,
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $this->anchor->subDays(2)->addMinutes(90) : null,
                        'email_approved_by_user_id' => in_array($supplierOrderStatus, [
                            SupplierOrderStatus::EmailPrepared,
                            SupplierOrderStatus::Sent,
                            SupplierOrderStatus::Confirmed,
                            SupplierOrderStatus::Delayed,
                            SupplierOrderStatus::Completed,
                        ], true) ? $users['admin']->getKey() : null,
                        'no_attachment_confirmed' => $scenarioIndex % 5 === 0,
                        'notes' => 'Comprehensive seeded supplier order scenario '.$scenarioIndex.'.',
                    ]
                );

                foreach ($proposalProducts as $itemIndex => $product) {
                    $orderedQuantity = 48 + ($scenarioIndex * 6) + ($itemIndex * 12);

                    SupplierOrderItem::query()->updateOrCreate(
                        [
                            'supplier_order_id' => $supplierOrder->getKey(),
                            'product_id' => $product->getKey(),
                        ],
                        [
                            'ordered_quantity' => $orderedQuantity,
                            'confirmed_quantity' => in_array($supplierOrderStatus, [
                                SupplierOrderStatus::Confirmed,
                                SupplierOrderStatus::Delayed,
                                SupplierOrderStatus::Completed,
                            ], true) ? $orderedQuantity - ($itemIndex === 0 && $scenarioIndex % 3 === 0 ? 6 : 0) : null,
                            'received_quantity' => $supplierOrderStatus === SupplierOrderStatus::Completed ? $orderedQuantity : null,
                            'damaged_quantity' => $supplierOrderStatus === SupplierOrderStatus::Completed && $itemIndex === 1 ? 1 : null,
                            'receiving_notes' => $supplierOrderStatus === SupplierOrderStatus::Completed && $itemIndex === 1
                                ? 'One unit damaged in demo receiving scenario.'
                                : null,
                            'unit_price' => 12.50 + $itemIndex + $scenarioIndex,
                            'currency' => 'EUR',
                            'status' => $supplierOrderStatus === SupplierOrderStatus::Completed ? 'received' : 'ordered',
                            'notes' => 'Seeded from order proposal item.',
                        ]
                    );
                }

                $this->seedOrderEmailAiAndLogistics(
                    $company,
                    $users,
                    $supplierOrder,
                    $supplier,
                    $carriers,
                    $templates,
                    $emailAccounts['manual'],
                    $scenarioIndex
                );

                $this->audit($company, $users['supply'], 'calculation_run.completed', $calculationRun, [
                    'proposal_id' => $proposal->getKey(),
                ], 100 + $scenarioIndex);
                $this->audit($company, $users['admin'], 'order_proposal.reviewed', $proposal, [
                    'status' => $proposal->status instanceof OrderProposalStatus ? $proposal->status->value : $proposal->status,
                ], 120 + $scenarioIndex);
                $this->audit($company, $users['supply'], 'supplier_order.created', $supplierOrder, [
                    'status' => $supplierOrder->status instanceof SupplierOrderStatus ? $supplierOrder->status->value : $supplierOrder->status,
                ], 140 + $scenarioIndex);
            }
        }
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<int, array<int, bool>>  $productIdsBySupplier
     * @return array<int, Product>
     */
    private function productsForSupplier(Supplier $supplier, array $products, array $productIdsBySupplier, int $offset, int $count): array
    {
        $matchingProducts = [];
        $productIds = $productIdsBySupplier[$supplier->getKey()] ?? [];

        foreach ($products as $product) {
            if (isset($productIds[$product->getKey()])) {
                $matchingProducts[] = $product;
            }
        }

        if ($matchingProducts === []) {
            $matchingProducts = $products;
        }

        $selected = [];

        for ($index = 0; $index < $count; $index++) {
            $selected[] = $matchingProducts[($offset + $index) % count($matchingProducts)];
        }

        return $selected;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Carrier>  $carriers
     * @param  array<string, FormTemplate>  $templates
     */
    private function seedOrderEmailAiAndLogistics(
        Company $company,
        array $users,
        SupplierOrder $supplierOrder,
        Supplier $supplier,
        array $carriers,
        array $templates,
        EmailAccount $emailAccount,
        int $scenarioIndex,
    ): void {
        $supplierContact = $supplier->contacts()->where('receives_orders', true)->first();
        $orderNumber = $supplierOrder->order_number;
        $threadId = 'lab-thread-'.$orderNumber;

        $outbound = EmailMessage::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'message_id' => 'lab-outbound-'.$orderNumber,
            ],
            [
                'email_account_id' => $emailAccount->getKey(),
                'direction' => EmailDirection::Outbound->value,
                'thread_id' => $threadId,
                'from_email' => $emailAccount->email_address,
                'to_json' => [$supplierContact?->email ?? 'orders@'.$supplier->code.'.example.test'],
                'cc_json' => ['logistics@procurement-lab.example.test'],
                'subject' => 'Purchase order '.$orderNumber,
                'body_text' => 'Please confirm quantities and ready date for '.$orderNumber.'.',
                'body_html' => null,
                'received_at' => null,
                'sent_at' => $supplierOrder->sent_at,
                'related_supplier_id' => $supplier->getKey(),
                'related_supplier_order_id' => $supplierOrder->getKey(),
                'status' => $supplierOrder->sent_at === null ? 'draft' : 'sent',
                'raw_headers_json' => ['demo' => true],
            ]
        );

        $supplierOrder->forceFill([
            'email_message_id' => $outbound->message_id,
        ])->save();

        EmailAttachment::query()->updateOrCreate(
            [
                'email_message_id' => $outbound->getKey(),
                'stored_path' => 'demo/orders/'.$orderNumber.'.csv',
            ],
            [
                'original_filename' => $orderNumber.'.csv',
                'mime_type' => 'text/csv',
                'size_bytes' => 2048 + $scenarioIndex,
                'checksum' => hash('sha256', 'outbound-'.$orderNumber),
            ]
        );

        $inbound = EmailMessage::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'message_id' => 'lab-inbound-confirmation-'.$orderNumber,
            ],
            [
                'email_account_id' => $emailAccount->getKey(),
                'direction' => EmailDirection::Inbound->value,
                'thread_id' => $threadId,
                'from_email' => $supplierContact?->email ?? 'orders@'.$supplier->code.'.example.test',
                'to_json' => [$emailAccount->email_address],
                'cc_json' => [],
                'subject' => 'Re: Purchase order '.$orderNumber,
                'body_text' => 'Confirmed '.$orderNumber.' with ready date '.$this->anchor->addDays(7 + $scenarioIndex)->toDateString().'.',
                'body_html' => null,
                'received_at' => $this->anchor->subDay()->addMinutes($scenarioIndex),
                'sent_at' => null,
                'related_supplier_id' => $supplier->getKey(),
                'related_supplier_order_id' => $supplierOrder->getKey(),
                'status' => 'received',
                'raw_headers_json' => ['demo' => true, 'mailbox' => 'manual'],
            ]
        );

        EmailAttachment::query()->updateOrCreate(
            [
                'email_message_id' => $inbound->getKey(),
                'stored_path' => 'demo/confirmations/'.$orderNumber.'.pdf',
            ],
            [
                'original_filename' => $orderNumber.'-confirmation.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 4096 + $scenarioIndex,
                'checksum' => hash('sha256', 'confirmation-'.$orderNumber),
            ]
        );

        $orderItems = $supplierOrder->items()->with('product')->get();
        $confirmedItems = $orderItems->map(fn (SupplierOrderItem $item, int $itemIndex): array => [
            'sku' => $item->product?->sku,
            'ordered_quantity' => (float) $item->ordered_quantity,
            'confirmed_quantity' => (float) $item->ordered_quantity - ($itemIndex === 0 && $scenarioIndex % 3 === 0 ? 6 : 0),
        ])->values()->all();

        $requiresReview = $scenarioIndex % 3 === 0;
        $extraction = AiEmailExtraction::query()->updateOrCreate(
            [
                'email_message_id' => $inbound->getKey(),
                'input_hash' => hash('sha256', 'supplier-confirmation-'.$orderNumber),
            ],
            [
                'provider' => 'demo_fake',
                'model' => 'demo-supplier-email-parser',
                'prompt_version' => AiPromptVersion::SupplierEmailParserV1->value,
                'output_json' => [
                    'email_type' => 'supplier_confirmation',
                    'supplier_order_number' => $orderNumber,
                    'supplier_reference' => 'CONF-'.$orderNumber,
                    'confirmed_items' => $confirmedItems,
                    'dates' => [
                        'confirmation_date' => $this->anchor->toDateString(),
                        'ready_date' => $this->anchor->addDays(7 + $scenarioIndex)->toDateString(),
                        'shipping_date' => $this->anchor->addDays(8 + $scenarioIndex)->toDateString(),
                        'expected_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                    ],
                    'discrepancies' => $requiresReview ? ['first_line_quantity_mismatch'] : [],
                    'requires_human_review' => true,
                ],
                'confidence' => $requiresReview ? 78.50 : 93.25,
                'requires_human_review' => true,
                'review_reason' => $requiresReview ? 'quantity_mismatch' : 'pending_human_approval',
                'reviewed_by_user_id' => $requiresReview ? null : $users['supply']->getKey(),
                'reviewed_at' => $requiresReview ? null : $this->anchor->subHours(4),
                'accepted_at' => $requiresReview ? null : $this->anchor->subHours(3),
                'rejected_at' => null,
            ]
        );

        $autofillRun = $this->seedFormAutofillRun(
            $company,
            $users,
            $inbound,
            $templates['supplier_confirmation_v2'],
            $extraction,
            $requiresReview ? FormAutofillRunStatus::NeedsReview : FormAutofillRunStatus::Validated,
            [
                'supplier_reference' => 'CONF-'.$orderNumber,
                'supplier_order_number' => $orderNumber,
                'ready_date' => $this->anchor->addDays(7 + $scenarioIndex)->toDateString(),
                'expected_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
            ],
            $scenarioIndex
        );

        $confirmation = SupplierConfirmation::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'supplier_order_id' => $supplierOrder->getKey(),
                'supplier_reference' => 'CONF-'.$orderNumber,
            ],
            [
                'email_message_id' => $inbound->getKey(),
                'confirmation_date' => $this->anchor->toDateString(),
                'ready_date' => $this->anchor->addDays(7 + $scenarioIndex)->toDateString(),
                'shipping_date' => $this->anchor->addDays(8 + $scenarioIndex)->toDateString(),
                'expected_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                'status' => $requiresReview
                    ? SupplierConfirmationStatus::QuantityMismatch->value
                    : SupplierConfirmationStatus::Confirmed->value,
                'discrepancy_summary' => $requiresReview ? 'First line confirmed below ordered quantity.' : null,
                'created_from_ai_extraction_id' => $extraction->getKey(),
                'created_from_form_autofill_run_id' => $autofillRun->getKey(),
                'source_type' => 'ai_email_extraction',
                'source_id' => $extraction->getKey(),
                'output_json' => $extraction->output_json,
                'discrepancies_json' => $requiresReview ? ['first_line_quantity_mismatch'] : [],
                'applied_by_user_id' => $requiresReview ? null : $users['supply']->getKey(),
                'applied_at' => $requiresReview ? null : $this->anchor->subHours(2),
            ]
        );

        foreach ($orderItems as $itemIndex => $item) {
            $confirmedQuantity = (float) $item->ordered_quantity - ($itemIndex === 0 && $requiresReview ? 6 : 0);
            $discrepancy = (float) $item->ordered_quantity - $confirmedQuantity;

            SupplierConfirmationItem::query()->updateOrCreate(
                [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                    'product_id' => $item->product_id,
                ],
                [
                    'ordered_quantity' => $item->ordered_quantity,
                    'confirmed_quantity' => $confirmedQuantity,
                    'discrepancy_quantity' => $discrepancy,
                    'status' => $discrepancy > 0 ? 'quantity_mismatch' : 'confirmed',
                    'notes' => $discrepancy > 0 ? 'Supplier confirmed a lower quantity in demo data.' : null,
                    'source_item_json' => [
                        'sku' => $item->product?->sku,
                        'line' => $itemIndex + 1,
                    ],
                    'matched_by' => 'sku',
                    'discrepancy_type' => $discrepancy > 0 ? 'short_confirmed' : null,
                    'discrepancies_json' => $discrepancy > 0 ? ['ordered_minus_confirmed' => $discrepancy] : [],
                ]
            );
        }

        $inboundOrder = InboundOrder::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'supplier_order_id' => $supplierOrder->getKey(),
                'order_number' => 'IN-'.$orderNumber,
            ],
            [
                'supplier_id' => $supplier->getKey(),
                'supplier_order_reference' => 'CONF-'.$orderNumber,
                'status' => $supplierOrder->status === SupplierOrderStatus::Completed ? 'received' : 'confirmed',
                'ordered_at' => $supplierOrder->order_date,
                'expected_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                'confirmed_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                'ready_date' => $this->anchor->addDays(7 + $scenarioIndex)->toDateString(),
                'shipped_date' => $this->anchor->addDays(8 + $scenarioIndex)->toDateString(),
                'notes' => 'Seeded inbound order linked to demo supplier order.',
            ]
        );

        foreach ($orderItems as $itemIndex => $item) {
            $confirmedQuantity = (float) $item->ordered_quantity - ($itemIndex === 0 && $requiresReview ? 6 : 0);

            InboundOrderItem::query()->updateOrCreate(
                [
                    'inbound_order_id' => $inboundOrder->getKey(),
                    'product_id' => $item->product_id,
                ],
                [
                    'ordered_quantity' => $item->ordered_quantity,
                    'confirmed_quantity' => $confirmedQuantity,
                    'received_quantity' => $supplierOrder->status === SupplierOrderStatus::Completed ? $confirmedQuantity : null,
                    'damaged_quantity' => $supplierOrder->status === SupplierOrderStatus::Completed && $itemIndex === 1 ? 1 : null,
                    'receiving_notes' => $supplierOrder->status === SupplierOrderStatus::Completed && $itemIndex === 1
                        ? 'One unit damaged in demo receiving scenario.'
                        : null,
                    'expected_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                    'confirmed_arrival_date' => $this->anchor->addDays(14 + $scenarioIndex)->toDateString(),
                    'status' => $supplierOrder->status === SupplierOrderStatus::Completed ? 'received' : 'confirmed',
                ]
            );
        }

        $selectedQuote = null;

        foreach (array_values($carriers) as $carrierIndex => $carrier) {
            $quoteEmail = EmailMessage::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'message_id' => 'lab-carrier-quote-'.$orderNumber.'-'.$carrier->code,
                ],
                [
                    'email_account_id' => $emailAccount->getKey(),
                    'direction' => EmailDirection::Inbound->value,
                    'thread_id' => 'carrier-'.$threadId,
                    'from_email' => 'quotes@'.Str::lower((string) $carrier->code).'.example.test',
                    'to_json' => [$emailAccount->email_address],
                    'cc_json' => [],
                    'subject' => 'Transport quote for '.$orderNumber,
                    'body_text' => 'Quote for '.$orderNumber.' price '.(420 + ($carrierIndex * 35) + $scenarioIndex).'.',
                    'body_html' => null,
                    'received_at' => $this->anchor->subHours(12)->addMinutes($carrierIndex),
                    'sent_at' => null,
                    'related_supplier_id' => $supplier->getKey(),
                    'related_supplier_order_id' => $supplierOrder->getKey(),
                    'status' => 'received',
                    'raw_headers_json' => ['demo' => true],
                ]
            );

            $quoteExtraction = AiEmailExtraction::query()->updateOrCreate(
                [
                    'email_message_id' => $quoteEmail->getKey(),
                    'input_hash' => hash('sha256', 'carrier-quote-'.$orderNumber.'-'.$carrier->code),
                ],
                [
                    'provider' => 'demo_fake',
                    'model' => 'demo-carrier-quote-parser',
                    'prompt_version' => AiPromptVersion::CarrierQuoteParserV1->value,
                    'output_json' => [
                        'carrier_name' => $carrier->name,
                        'price' => 420 + ($carrierIndex * 35) + $scenarioIndex,
                        'currency' => 'EUR',
                        'pickup_date' => $this->anchor->addDays(8 + $scenarioIndex)->toDateString(),
                        'delivery_date' => $this->anchor->addDays(13 + $scenarioIndex + $carrierIndex)->toDateString(),
                    ],
                    'confidence' => 86.00 + $carrierIndex,
                    'requires_human_review' => true,
                    'review_reason' => 'carrier_selection_requires_human_approval',
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                    'accepted_at' => null,
                    'rejected_at' => null,
                ]
            );

            $carrierAutofill = $this->seedFormAutofillRun(
                $company,
                $users,
                $quoteEmail,
                $templates['carrier_quote_v2'],
                $quoteExtraction,
                FormAutofillRunStatus::NeedsReview,
                [
                    'carrier_name' => $carrier->name,
                    'price' => (string) (420 + ($carrierIndex * 35) + $scenarioIndex),
                    'currency' => 'EUR',
                    'delivery_date' => $this->anchor->addDays(13 + $scenarioIndex + $carrierIndex)->toDateString(),
                ],
                $scenarioIndex + $carrierIndex
            );

            $isSelected = $carrierIndex === ($scenarioIndex % count($carriers));
            $isRejected = ! $isSelected && $carrierIndex > 2;
            $quote = CarrierQuote::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'supplier_order_id' => $supplierOrder->getKey(),
                    'carrier_id' => $carrier->getKey(),
                    'source_type' => 'ai_email_extraction',
                    'source_id' => $quoteExtraction->getKey(),
                ],
                [
                    'email_message_id' => $quoteEmail->getKey(),
                    'price' => 420 + ($carrierIndex * 35) + $scenarioIndex,
                    'currency' => 'EUR',
                    'pickup_date' => $this->anchor->addDays(8 + $scenarioIndex)->toDateString(),
                    'delivery_date' => $this->anchor->addDays(13 + $scenarioIndex + $carrierIndex)->toDateString(),
                    'transit_days' => 5 + $carrierIndex,
                    'conditions' => $carrierIndex === 0 ? 'Fast pickup, standard insurance.' : 'Demo quote conditions.',
                    'reliability_score' => $carrier->reliability_score,
                    'calculated_score' => 95 - ($carrierIndex * 4) + ($scenarioIndex / 10),
                    'score_explanation_json' => [
                        'price_weight' => 0.50,
                        'transit_weight' => 0.30,
                        'reliability_weight' => 0.20,
                    ],
                    'status' => $isSelected
                        ? CarrierQuoteStatus::Selected->value
                        : ($isRejected ? CarrierQuoteStatus::Rejected->value : CarrierQuoteStatus::Received->value),
                    'created_from_ai_extraction_id' => $quoteExtraction->getKey(),
                    'created_from_form_autofill_run_id' => $carrierAutofill->getKey(),
                    'created_by_user_id' => $users['logistics']->getKey(),
                    'selected_by_user_id' => $isSelected ? $users['logistics']->getKey() : null,
                    'selected_at' => $isSelected ? $this->anchor->subHours(1) : null,
                    'rejected_by_user_id' => $isRejected ? $users['logistics']->getKey() : null,
                    'rejected_at' => $isRejected ? $this->anchor->subMinutes(45) : null,
                    'rejection_reason' => $isRejected ? 'Price or transit time is worse than alternatives.' : null,
                    'validation_errors_json' => [],
                    'warnings_json' => $carrierIndex === 4 ? ['longer_transit_time'] : [],
                ]
            );

            if ($isSelected) {
                $selectedQuote = $quote;
            }
        }

        $status = match (true) {
            $supplierOrder->status === SupplierOrderStatus::Delayed => LogisticsStatus::Delayed,
            $supplierOrder->status === SupplierOrderStatus::Completed => LogisticsStatus::Completed,
            $selectedQuote instanceof CarrierQuote => LogisticsStatus::PickupScheduled,
            default => LogisticsStatus::Planned,
        };

        $logisticsRecord = LogisticsRecord::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'supplier_order_id' => $supplierOrder->getKey(),
            ],
            [
                'supplier_id' => $supplier->getKey(),
                'carrier_id' => $selectedQuote?->carrier_id,
                'supplier_confirmation_id' => $confirmation->getKey(),
                'selected_carrier_quote_id' => $selectedQuote?->getKey(),
                'order_date' => $supplierOrder->order_date,
                'confirmation_date' => $confirmation->confirmation_date,
                'ready_date' => $confirmation->ready_date,
                'pickup_date' => $selectedQuote?->pickup_date,
                'delivery_date' => $selectedQuote?->delivery_date,
                'actual_received_date' => $supplierOrder->status === SupplierOrderStatus::Completed
                    ? $this->anchor->addDays(20 + $scenarioIndex)->toDateString()
                    : null,
                'transport_price' => $selectedQuote?->price,
                'currency' => 'EUR',
                'status' => $status->value,
                'external_sheet_reference' => 'DEMO-SHEET-'.$orderNumber,
                'receiving_discrepancies_json' => $supplierOrder->status === SupplierOrderStatus::Completed
                    ? ['damaged_lines' => 1]
                    : [],
                'received_by_user_id' => $supplierOrder->status === SupplierOrderStatus::Completed
                    ? $users['logistics']->getKey()
                    : null,
                'received_at' => $supplierOrder->status === SupplierOrderStatus::Completed
                    ? $this->anchor->addDays(20 + $scenarioIndex)
                    : null,
                'last_delay_checked_at' => $supplierOrder->status === SupplierOrderStatus::Delayed
                    ? $this->anchor->subMinutes(30)
                    : null,
                'delay_reason' => $supplierOrder->status === SupplierOrderStatus::Delayed
                    ? 'Supplier shifted ready date in demo scenario.'
                    : null,
                'notes' => 'Seeded logistics record connected to supplier confirmation and carrier quotes.',
            ]
        );

        ExportFile::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'filename' => $orderNumber.'-export.csv',
            ],
            [
                'export_type' => 'supplier_order',
                'related_model_type' => SupplierOrder::class,
                'related_model_id' => $supplierOrder->getKey(),
                'stored_path' => 'demo/exports/'.$orderNumber.'-export.csv',
                'mime_type' => 'text/csv',
                'status' => ExportFileStatus::Stored->value,
                'created_by_user_id' => $users['supply']->getKey(),
            ]
        );

        $this->audit($company, $users['supply'], 'supplier_email.sent_or_prepared', $outbound, [
            'supplier_order_id' => $supplierOrder->getKey(),
        ], 200 + $scenarioIndex);
        $this->audit($company, $users['supply'], 'inbound_email.processed', $inbound, [
            'ai_email_extraction_id' => $extraction->getKey(),
        ], 220 + $scenarioIndex);
        $this->audit($company, $users['supply'], 'ai_extraction.review_seeded', $extraction, [
            'requires_human_review' => true,
        ], 240 + $scenarioIndex);
        $this->audit($company, $users['supply'], 'supplier_confirmation.created', $confirmation, [
            'status' => $confirmation->status instanceof SupplierConfirmationStatus ? $confirmation->status->value : $confirmation->status,
        ], 260 + $scenarioIndex);
        $this->audit($company, $users['logistics'], 'logistics_record.seeded', $logisticsRecord, [
            'status' => $logisticsRecord->status instanceof LogisticsStatus ? $logisticsRecord->status->value : $logisticsRecord->status,
        ], 280 + $scenarioIndex);
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, string>  $values
     */
    private function seedFormAutofillRun(
        Company $company,
        array $users,
        EmailMessage $emailMessage,
        FormTemplate $template,
        AiEmailExtraction $extraction,
        FormAutofillRunStatus $status,
        array $values,
        int $offset,
    ): FormAutofillRun {
        $run = FormAutofillRun::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'email_message_id' => $emailMessage->getKey(),
                'form_template_id' => $template->getKey(),
            ],
            [
                'ai_email_extraction_id' => $extraction->getKey(),
                'status' => $status->value,
                'confidence' => $status === FormAutofillRunStatus::NeedsReview ? 82.50 : 94.00,
                'raw_input_hash' => hash('sha256', 'autofill-'.$emailMessage->message_id.'-'.$template->code),
                'suggested_values_json' => $values,
                'validation_errors_json' => [],
                'warnings_json' => $status === FormAutofillRunStatus::NeedsReview ? ['human_review_required'] : [],
                'user_changes_json' => $status === FormAutofillRunStatus::Validated ? ['reviewed' => true] : [],
                'created_by_user_id' => $users['supply']->getKey(),
                'reviewed_by_user_id' => $status === FormAutofillRunStatus::Validated ? $users['supply']->getKey() : null,
                'applied_by_user_id' => null,
                'applied_at' => null,
            ]
        );

        foreach ($values as $fieldKey => $value) {
            FormAutofillFieldValue::query()->updateOrCreate(
                [
                    'form_autofill_run_id' => $run->getKey(),
                    'field_key' => $fieldKey,
                ],
                [
                    'extracted_value' => $value,
                    'normalized_value' => $value,
                    'final_value' => $status === FormAutofillRunStatus::Validated ? $value : null,
                    'confidence' => $status === FormAutofillRunStatus::NeedsReview ? 82.50 : 94.00,
                    'source_excerpt' => 'Demo source excerpt for '.$fieldKey.'.',
                    'requires_review' => $status === FormAutofillRunStatus::NeedsReview,
                    'review_reason' => $status === FormAutofillRunStatus::NeedsReview ? 'Seeded human review queue.' : null,
                    'accepted_by_user_id' => $status === FormAutofillRunStatus::Validated ? $users['supply']->getKey() : null,
                    'accepted_at' => $status === FormAutofillRunStatus::Validated ? $this->anchor->subMinutes($offset) : null,
                ]
            );
        }

        FormAutofillOutput::query()->updateOrCreate(
            [
                'form_autofill_run_id' => $run->getKey(),
                'filename' => 'autofill-'.$run->getKey().'.json',
            ],
            [
                'output_type' => 'json_preview',
                'stored_path' => 'demo/autofill/autofill-'.$run->getKey().'.json',
                'content_json' => $values,
                'status' => 'created',
                'created_by_user_id' => $users['supply']->getKey(),
            ]
        );

        return $run;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<int, Product>  $products
     */
    private function seedLegacySupplyDataIfTablesExist(array $users, array $products): void
    {
        $requiredTables = [
            'manufacturers',
            'stock_items',
            'supply_orders',
            'manufacturer_emails',
            'manufacturer_form_submissions',
            'ai_suggestions',
            'human_reviews',
            'logistics_options',
            'logistics_entries',
            'supply_audit_events',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $manufacturer = Manufacturer::query()->updateOrCreate(
            ['email' => 'legacy-orders@example.test'],
            [
                'name' => 'Legacy Demo Manufacturer',
                'order_form_url' => 'https://legacy-manufacturer.example.test/orders',
            ]
        );

        foreach (array_slice($products, 0, 5) as $index => $product) {
            StockItem::query()->updateOrCreate(
                ['product_id' => $product->getKey()],
                [
                    'available_quantity' => 30 + $index,
                    'incoming_quantity' => 100 + ($index * 12),
                    'reserved_quantity' => 5 + $index,
                ]
            );

            $supplyOrder = SupplyOrder::query()->updateOrCreate(
                ['order_number' => 'LEGACY-SO-20260703-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'manufacturer_id' => $manufacturer->getKey(),
                    'product_id' => $product->getKey(),
                    'created_by_id' => $users['supply']->getKey(),
                    'status' => SupplyOrderStatus::Submitted->value,
                    'customer_reference' => 'LEGACY-CUSTOMER-'.$index,
                    'requested_quantity' => 150,
                    'available_quantity' => 30 + $index,
                    'required_quantity' => 120 - $index,
                    'manufacturer_quantity' => 144,
                    'reserve_percent' => 5,
                    'manufacturer_confirmation_number' => 'LEGACY-CONF-'.$index,
                    'manufacturer_ready_on' => $this->anchor->addDays(9 + $index)->toDateString(),
                    'submitted_at' => $this->anchor->subDay(),
                ]
            );

            $email = ManufacturerEmail::query()->updateOrCreate(
                ['message_id' => 'legacy-email-'.$supplyOrder->order_number],
                [
                    'supply_order_id' => $supplyOrder->getKey(),
                    'processed_by_id' => $users['supply']->getKey(),
                    'from_email' => $manufacturer->email,
                    'subject' => 'Confirmation '.$supplyOrder->order_number,
                    'body' => 'Legacy confirmation body.',
                    'extracted_order_number' => $supplyOrder->order_number,
                    'extracted_confirmation_number' => 'LEGACY-CONF-'.$index,
                    'extracted_ready_on' => $this->anchor->addDays(9 + $index)->toDateString(),
                    'extracted_pickup_on' => $this->anchor->addDays(10 + $index)->toDateString(),
                    'received_at' => $this->anchor->subHours(4),
                    'processed_at' => $this->anchor->subHours(3),
                    'automation_source' => 'demo_seed',
                ]
            );

            $suggestion = AiSuggestion::query()->updateOrCreate(
                [
                    'supply_order_id' => $supplyOrder->getKey(),
                    'manufacturer_email_id' => $email->getKey(),
                    'type' => AiSuggestionType::EmailConfirmation->value,
                ],
                [
                    'created_by_id' => $users['supply']->getKey(),
                    'reviewed_by_id' => $index % 2 === 0 ? $users['admin']->getKey() : null,
                    'applied_by_id' => null,
                    'status' => $index % 2 === 0 ? AiSuggestionStatus::Approved->value : AiSuggestionStatus::PendingReview->value,
                    'confidence_score' => 85,
                    'requires_review' => true,
                    'source_adapter' => 'demo_seed',
                    'payload' => [
                        'confirmation_number' => 'LEGACY-CONF-'.$index,
                    ],
                    'conflicts' => [],
                    'notes' => 'Legacy suggestion seeded only when legacy tables exist.',
                    'reviewed_at' => $index % 2 === 0 ? $this->anchor->subHours(2) : null,
                    'applied_at' => null,
                ]
            );

            HumanReview::query()->updateOrCreate(
                [
                    'ai_suggestion_id' => $suggestion->getKey(),
                    'reason' => 'legacy_demo_review',
                ],
                [
                    'assigned_to_id' => $users['supply']->getKey(),
                    'reviewed_by_id' => $index % 2 === 0 ? $users['admin']->getKey() : null,
                    'status' => $index % 2 === 0 ? HumanReviewStatus::Approved->value : HumanReviewStatus::Pending->value,
                    'priority' => $index % 2 === 0 ? 'normal' : 'high',
                    'context' => ['supply_order_id' => $supplyOrder->getKey()],
                    'reviewed_at' => $index % 2 === 0 ? $this->anchor->subHours(2) : null,
                ]
            );

            ManufacturerFormSubmission::query()->updateOrCreate(
                [
                    'supply_order_id' => $supplyOrder->getKey(),
                    'form_url' => $manufacturer->order_form_url,
                ],
                [
                    'submitted_by_id' => $users['supply']->getKey(),
                    'status' => ManufacturerFormSubmissionStatus::Ready->value,
                    'payload' => [
                        'order_number' => $supplyOrder->order_number,
                        'sku' => $product->sku,
                    ],
                    'automation_source' => 'demo_seed',
                    'submitted_at' => null,
                ]
            );

            $option = LogisticsOption::query()->updateOrCreate(
                [
                    'supply_order_id' => $supplyOrder->getKey(),
                    'carrier_name' => 'Legacy Road Carrier',
                ],
                [
                    'service_name' => 'Standard road',
                    'price_cents' => 42000 + ($index * 1000),
                    'currency' => 'EUR',
                    'transit_days' => 5,
                    'pickup_on' => $this->anchor->addDays(10 + $index)->toDateString(),
                    'delivery_on' => $this->anchor->addDays(15 + $index)->toDateString(),
                    'selected' => true,
                ]
            );

            LogisticsEntry::query()->updateOrCreate(
                ['supply_order_id' => $supplyOrder->getKey()],
                [
                    'logistics_option_id' => $option->getKey(),
                    'updated_by_id' => $users['logistics']->getKey(),
                    'carrier_name' => $option->carrier_name,
                    'price_cents' => $option->price_cents,
                    'currency' => $option->currency,
                    'pickup_on' => $option->pickup_on,
                    'delivery_on' => $option->delivery_on,
                    'status' => LogisticsStatus::Planned->value,
                    'compared_at' => $this->anchor->subHour(),
                ]
            );

            SupplyAuditEvent::query()->updateOrCreate(
                [
                    'auditable_type' => SupplyOrder::class,
                    'auditable_id' => $supplyOrder->getKey(),
                    'event' => 'legacy_supply_order.seeded',
                ],
                [
                    'actor_id' => $users['supply']->getKey(),
                    'metadata' => ['source' => 'ComprehensiveProcurementDemoSeeder'],
                    'occurred_at' => $this->anchor,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(Company $company, User $user, string $eventType, Model $auditable, array $metadata, int $minuteOffset): void
    {
        AuditLog::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'event_type' => $eventType,
                'auditable_type' => $auditable::class,
                'auditable_id' => $auditable->getKey(),
            ],
            [
                'user_id' => $user->getKey(),
                'old_values_json' => [],
                'new_values_json' => [
                    'seeded' => true,
                ],
                'metadata_json' => $metadata,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'ComprehensiveProcurementDemoSeeder',
                'created_at' => $this->anchor->addMinutes($minuteOffset),
            ]
        );
    }
}
