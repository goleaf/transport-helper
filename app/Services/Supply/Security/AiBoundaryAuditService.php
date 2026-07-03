<?php

namespace App\Services\Supply\Security;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class AiBoundaryAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [
            $this->calculationEngineBoundaryCheck(),
            $this->aiMutationBoundaryCheck(),
            $this->formAutofillBoundaryCheck(),
            $this->carrierScoringSelectionBoundaryCheck(),
            $this->supplierEmailApprovalBoundaryCheck(),
        ];

        return [
            'status' => $this->statusFromChecks($checks),
            'checks' => $checks,
            'warnings' => collect($checks)->where('status', 'warning')->values()->all(),
            'errors' => collect($checks)->where('status', 'error')->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculationEngineBoundaryCheck(): array
    {
        $matches = $this->scanTerms(app_path('Services/Supply/Calculation'), [
            'OpenAI',
            'AiEmail',
            'EmailMessage',
            'FormAutofill',
            'Guzzle',
            'Http::',
        ]);

        return $this->check('calculation_engine_boundary', $matches === [] ? 'ok' : 'error', $matches === [] ? 'Calculation engine has no AI/email/form dependencies.' : 'Calculation engine references forbidden dependencies.', [
            'matches' => $matches,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function aiMutationBoundaryCheck(): array
    {
        $matches = $this->scanTerms(app_path('Services/AI'), [
            'SupplierOrderItem::',
            'SupplierConfirmation::create',
            'SupplierConfirmation::query()->create',
            'CarrierQuoteStatus::Selected',
            "'selected'",
            'LogisticsRecord::query()->update',
        ]);

        return $this->check('ai_direct_mutation_boundary', $matches === [] ? 'ok' : 'error', $matches === [] ? 'AI services do not directly mutate business records.' : 'AI services reference direct business mutations.', [
            'matches' => $matches,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formAutofillBoundaryCheck(): array
    {
        $matches = $this->scanTerms(app_path('Services/Forms'), [
            'SupplierConfirmation::create',
            'SupplierConfirmation::query()->create',
            'CarrierQuote::create',
            'CarrierQuote::query()->create',
            'LogisticsRecord::query()->update',
        ]);

        return $this->check('form_autofill_boundary', $matches === [] ? 'ok' : 'error', $matches === [] ? 'Form autofill does not directly create confirmations, quotes or logistics records.' : 'Form autofill references direct business mutation.', [
            'matches' => $matches,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function carrierScoringSelectionBoundaryCheck(): array
    {
        $files = [
            app_path('Services/Supply/Transport/CarrierQuoteScoringService.php'),
            app_path('Services/Supply/Transport/CarrierQuoteComparisonService.php'),
        ];
        $matches = collect($files)
            ->filter(fn (string $file): bool => is_file($file))
            ->flatMap(function (string $file): array {
                $source = file_get_contents($file) ?: '';
                $terms = ['CarrierQuoteStatus::Selected', "'selected'", '"selected"', 'selected_at'];

                return collect($terms)
                    ->filter(fn (string $term): bool => str_contains($source, $term))
                    ->map(fn (string $term): array => [
                        'file' => str_replace(base_path().'/', '', $file),
                        'term' => $term,
                    ])
                    ->all();
            })
            ->values()
            ->all();

        return $this->check('carrier_scoring_selection_boundary', $matches === [] ? 'ok' : 'error', $matches === [] ? 'Scoring and comparison do not select carriers.' : 'Scoring or comparison references selection fields.', [
            'matches' => $matches,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierEmailApprovalBoundaryCheck(): array
    {
        $path = app_path('Services/Supply/SupplierOrders/SupplierOrderSendService.php');
        $source = is_file($path) ? (file_get_contents($path) ?: '') : '';
        $hasApprovalGuard = str_contains($source, 'must be approved before it can be sent')
            && str_contains($source, 'SupplierOrderStatus::Approved')
            && str_contains($source, "\$email->status !== 'approved'");

        return $this->check('supplier_email_approval_boundary', $hasApprovalGuard ? 'ok' : 'error', $hasApprovalGuard ? 'Supplier email send service enforces approval.' : 'Supplier email approval guard was not found.');
    }

    /**
     * @return list<array{file:string, term:string}>
     */
    private function scanTerms(string $directory, array $terms): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
            ->flatMap(function (SplFileInfo $file) use ($terms): array {
                $source = file_get_contents($file->getPathname()) ?: '';

                return collect($terms)
                    ->filter(fn (string $term): bool => str_contains($source, $term))
                    ->map(fn (string $term): array => [
                        'file' => str_replace(base_path().'/', '', $file->getPathname()),
                        'term' => $term,
                    ])
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function check(string $name, string $status, string $message, array $metadata = []): array
    {
        return compact('name', 'status', 'message', 'metadata');
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function statusFromChecks(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }
}
