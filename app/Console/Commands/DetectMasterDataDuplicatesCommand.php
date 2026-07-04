<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Supply\MasterData\MasterDataDuplicateDetectionService;
use App\Services\Supply\MasterData\ProductMergeProposalService;
use App\Services\Supply\MasterData\SupplierMergeProposalService;
use Illuminate\Console\Command;

class DetectMasterDataDuplicatesCommand extends Command
{
    protected $signature = 'supply:detect-master-data-duplicates
                            {--company_id= : Company id}
                            {--json : Output JSON}
                            {--create-proposals : Create draft merge proposals for top suggestions}';

    protected $description = 'Detect possible product and supplier duplicates without merging records.';

    public function handle(
        MasterDataDuplicateDetectionService $detectionService,
        ProductMergeProposalService $productMergeProposalService,
        SupplierMergeProposalService $supplierMergeProposalService,
    ): int {
        $company = $this->company();

        if (! $company instanceof Company) {
            $this->error('No company available for duplicate detection.');

            return self::FAILURE;
        }

        $productSuggestions = $detectionService->detectProductDuplicates($company);
        $supplierSuggestions = $detectionService->detectSupplierDuplicates($company);
        $skuConflicts = $detectionService->detectSupplierSkuConflicts($company);
        $createdProposalIds = $this->option('create-proposals')
            ? $this->createProposals($productSuggestions, $supplierSuggestions, $productMergeProposalService, $supplierMergeProposalService)
            : [];

        $result = [
            'status' => 'ok',
            'product_duplicates' => $productSuggestions,
            'supplier_duplicates' => $supplierSuggestions,
            'supplier_sku_conflicts' => $skuConflicts,
            'created_merge_proposal_ids' => $createdProposalIds,
            'merge_executed' => false,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Duplicate detection completed. No records were merged.');
        $this->line('Product suggestions: '.count($productSuggestions));
        $this->line('Supplier suggestions: '.count($supplierSuggestions));
        $this->line('Supplier SKU conflicts: '.count($skuConflicts));
        $this->line('Created merge proposals: '.count($createdProposalIds));

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string,mixed>>  $productSuggestions
     * @param  list<array<string,mixed>>  $supplierSuggestions
     * @return list<int>
     */
    private function createProposals(array $productSuggestions, array $supplierSuggestions, ProductMergeProposalService $productService, SupplierMergeProposalService $supplierService): array
    {
        $user = User::query()->where('role', 'admin')->first();

        if (! $user instanceof User) {
            return [];
        }

        $ids = [];
        foreach (array_slice($productSuggestions, 0, 5) as $suggestion) {
            $source = Product::query()->find($suggestion['source_id']);
            $target = Product::query()->find($suggestion['target_id']);
            if ($source instanceof Product && $target instanceof Product) {
                $ids[] = $productService->createProposal($source, $target, $user, 'Duplicate detection suggestion.')['proposal']->id;
            }
        }

        foreach (array_slice($supplierSuggestions, 0, 5) as $suggestion) {
            $source = Supplier::query()->find($suggestion['source_id']);
            $target = Supplier::query()->find($suggestion['target_id']);
            if ($source instanceof Supplier && $target instanceof Supplier) {
                $ids[] = $supplierService->createProposal($source, $target, $user, 'Duplicate detection suggestion.')['proposal']->id;
            }
        }

        return $ids;
    }

    private function company(): ?Company
    {
        return Company::query()
            ->when($this->option('company_id'), fn ($query) => $query->whereKey($this->option('company_id')))
            ->select(['id', 'name', 'code', 'timezone', 'default_currency'])
            ->orderBy('id')
            ->first();
    }
}
