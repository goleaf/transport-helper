<?php

namespace App\Services\Supply;

use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\InboundOrderItem;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\SupplierProductRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderProposalGenerationService
{
    public function __construct(
        private OrderNeedCalculator $orderNeedCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function generate(array $parameters): array
    {
        $companyId = $this->requiredInt($parameters, 'company_id');
        $supplierId = $this->requiredInt($parameters, 'supplier_id');
        $dates = $this->dates($parameters);
        $rules = $this->activeSupplierProductRules($companyId, $supplierId);
        $productIds = $rules->pluck('product_id')->values()->all();
        $salesBuckets = $this->salesBuckets($companyId, $productIds, $dates);
        $latestStockSnapshots = $this->latestStockSnapshots($companyId, $productIds, $dates['t0_date']);
        $inboundBuckets = $this->inboundBuckets($companyId, $supplierId, $productIds, $dates);
        $reservationBuckets = $this->reservationBuckets($companyId, $productIds, $dates['t3_date']);
        $calculations = [];

        foreach ($rules as $rule) {
            $productId = (int) $rule->product_id;
            $input = [
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'product_id' => $productId,
                't0_date' => $dates['t0_date']->toDateString(),
                't1_date' => $dates['t1_date']->toDateString(),
                't2_date' => $dates['t2_date']->toDateString(),
                't3_date' => $dates['t3_date']->toDateString(),
                'current_year_sales_for_trend' => $salesBuckets['current_trend'][$productId] ?? 0.0,
                'last_year_sales_for_trend' => $salesBuckets['last_trend'][$productId] ?? 0.0,
                'last_year_sales_t0_t1' => $salesBuckets['last_year_t0_t1'][$productId] ?? 0.0,
                'last_year_sales_t1_t2' => $salesBuckets['last_year_t1_t2'][$productId] ?? 0.0,
                'last_year_sales_t2_t3' => $salesBuckets['last_year_t2_t3'][$productId] ?? 0.0,
                'free_stock' => $latestStockSnapshots[$productId] ?? 0.0,
                'inbound_until_t1' => $inboundBuckets['until_t1'][$productId] ?? 0.0,
                'inbound_t1_t3' => $inboundBuckets['t1_t3'][$productId] ?? 0.0,
                'reserved_quantity' => $reservationBuckets[$productId] ?? 0.0,
                'moq' => $rule->moq,
                'pack_multiple' => $rule->pack_multiple,
                'pallet_quantity' => $rule->pallet_quantity,
                'min_transport_quantity' => $rule->min_transport_quantity,
                'rounding_strategy' => $parameters['rounding_strategy'] ?? [],
                'reservation_strategy' => $parameters['reservation_strategy'] ?? 'include',
                'safety_days_rule' => $parameters['safety_days_rule'] ?? $rule->safety_rule_type,
                'strategic_minimum_order_enabled' => (bool) ($parameters['strategic_minimum_order_enabled'] ?? false),
            ];

            if (array_key_exists('fallback_strategy', $parameters)) {
                $input['fallback_strategy'] = $parameters['fallback_strategy'];
            }

            $calculations[] = [
                'rule' => $rule,
                'input' => $input,
                'result' => $this->orderNeedCalculator->calculate($input),
            ];
        }

        return DB::transaction(function () use ($companyId, $supplierId, $parameters, $dates, $calculations): array {
            $requiresHumanReview = $this->calculationsRequireHumanReview($calculations);
            $now = now();
            $calculationRun = CalculationRun::query()->create([
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'calculation_date' => ($parameters['calculation_date'] ?? $dates['t0_date']->toDateString()),
                'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
                'parameters_json' => $parameters,
                'status' => $requiresHumanReview ? 'needs_review' : 'completed',
                'started_by_user_id' => $parameters['started_by_user_id'] ?? $parameters['created_by_user_id'] ?? null,
                'started_at' => $now,
                'finished_at' => $now,
            ]);

            $orderProposal = OrderProposal::query()->create([
                'company_id' => $companyId,
                'calculation_run_id' => $calculationRun->getKey(),
                'supplier_id' => $supplierId,
                'status' => $requiresHumanReview ? 'needs_review' : 'draft',
                'total_lines' => count($calculations),
                'created_by_user_id' => $parameters['created_by_user_id'] ?? null,
                'approved_by_user_id' => null,
                'approved_at' => null,
                'notes' => $parameters['notes'] ?? null,
            ]);

            $itemRows = [];

            foreach ($calculations as $calculation) {
                $result = $calculation['result'];
                $input = $calculation['input'];

                $itemRows[] = [
                    'order_proposal_id' => $orderProposal->getKey(),
                    'product_id' => $input['product_id'],
                    't0_date' => $input['t0_date'],
                    't1_date' => $input['t1_date'],
                    't2_date' => $input['t2_date'],
                    't3_date' => $input['t3_date'],
                    'trend' => $result['trend'],
                    'need_t0_t1' => $result['need_t0_t1'],
                    'stock_t1' => $result['stock_t1'],
                    'need_t1_t2' => $result['need_t1_t2'],
                    'safety_stock' => $result['safety_stock'],
                    'inbound_until_t1' => $input['inbound_until_t1'],
                    'inbound_t1_t3' => $input['inbound_t1_t3'],
                    'reserved_quantity' => $input['reserved_quantity'],
                    'raw_need' => $result['raw_need'],
                    'moq_applied' => $result['applied_rules']['moq']['value'] ?? null,
                    'pack_multiple_applied' => $result['applied_rules']['pack_multiple']['value'] ?? null,
                    'pallet_quantity_applied' => $result['applied_rules']['pallet_quantity']['value'] ?? null,
                    'recommended_quantity' => $result['recommended_quantity'],
                    'approved_quantity' => null,
                    'user_adjusted_quantity' => null,
                    'adjustment_reason' => null,
                    'explanation_json' => json_encode($result['explanation'], JSON_THROW_ON_ERROR),
                    'warnings_json' => json_encode($result['warnings'], JSON_THROW_ON_ERROR),
                    'requires_human_review' => $result['requires_human_review'],
                    'status' => $result['requires_human_review'] ? 'needs_review' : 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($itemRows !== []) {
                OrderProposalItem::query()->insert($itemRows);
            }

            AuditLog::query()->create([
                'company_id' => $companyId,
                'user_id' => $parameters['created_by_user_id'] ?? null,
                'event_type' => 'order_proposal.generated',
                'auditable_type' => $orderProposal->getMorphClass(),
                'auditable_id' => $orderProposal->getKey(),
                'old_values_json' => null,
                'new_values_json' => [
                    'calculation_run_id' => $calculationRun->getKey(),
                    'order_proposal_id' => $orderProposal->getKey(),
                    'total_lines' => count($calculations),
                    'status' => $orderProposal->status?->value ?? $orderProposal->status,
                ],
                'metadata_json' => [
                    'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
                    'requires_human_review' => $requiresHumanReview,
                ],
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => $now,
            ]);

            return [
                'calculation_run' => $calculationRun->refresh(),
                'order_proposal' => $orderProposal->load(['items.product', 'supplier', 'calculationRun']),
                'items' => $orderProposal->items,
            ];
        });
    }

    /**
     * @return Collection<int, SupplierProductRule>
     */
    private function activeSupplierProductRules(int $companyId, int $supplierId): Collection
    {
        return SupplierProductRule::query()
            ->select([
                'id',
                'supplier_id',
                'product_id',
                'supplier_sku',
                'moq',
                'pack_multiple',
                'pallet_quantity',
                'min_transport_quantity',
                'lead_time_days',
                'safety_days',
                'safety_rule_type',
                'transport_rule_type',
                'order_enabled',
            ])
            ->with(['product' => fn ($query) => $query->select(['id', 'company_id', 'sku', 'name', 'is_active'])])
            ->where('supplier_id', $supplierId)
            ->where('order_enabled', true)
            ->whereHas('product', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('is_active', true))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<string, CarbonImmutable>  $dates
     * @return array<string, array<int, float>>
     */
    private function salesBuckets(int $companyId, array $productIds, array $dates): array
    {
        $buckets = [
            'current_trend' => [],
            'last_trend' => [],
            'last_year_t0_t1' => [],
            'last_year_t1_t2' => [],
            'last_year_t2_t3' => [],
        ];

        if ($productIds === []) {
            return $buckets;
        }

        $minDate = $this->minDate([
            $dates['current_trend_start_date'],
            $dates['last_trend_start_date'],
            $dates['last_year_t0_date'],
        ]);
        $maxDate = $this->maxDate([
            $dates['current_trend_end_date'],
            $dates['last_trend_end_date'],
            $dates['last_year_t3_date'],
        ]);

        $salesRows = SalesHistory::query()
            ->select(['product_id', 'sales_date', 'quantity'])
            ->where('company_id', $companyId)
            ->whereIn('product_id', $productIds)
            ->where('sales_date', '>=', $minDate->toDateString())
            ->where('sales_date', '<', $maxDate->toDateString())
            ->get();

        foreach ($salesRows as $salesRow) {
            $productId = (int) $salesRow->product_id;
            $salesDate = CarbonImmutable::parse($salesRow->sales_date);
            $quantity = (float) $salesRow->quantity;

            if ($this->dateInRange($salesDate, $dates['current_trend_start_date'], $dates['current_trend_end_date'])) {
                $buckets['current_trend'][$productId] = ($buckets['current_trend'][$productId] ?? 0.0) + $quantity;
            }

            if ($this->dateInRange($salesDate, $dates['last_trend_start_date'], $dates['last_trend_end_date'])) {
                $buckets['last_trend'][$productId] = ($buckets['last_trend'][$productId] ?? 0.0) + $quantity;
            }

            if ($this->dateInRange($salesDate, $dates['last_year_t0_date'], $dates['last_year_t1_date'])) {
                $buckets['last_year_t0_t1'][$productId] = ($buckets['last_year_t0_t1'][$productId] ?? 0.0) + $quantity;
            }

            if ($this->dateInRange($salesDate, $dates['last_year_t1_date'], $dates['last_year_t2_date'])) {
                $buckets['last_year_t1_t2'][$productId] = ($buckets['last_year_t1_t2'][$productId] ?? 0.0) + $quantity;
            }

            if ($this->dateInRange($salesDate, $dates['last_year_t2_date'], $dates['last_year_t3_date'])) {
                $buckets['last_year_t2_t3'][$productId] = ($buckets['last_year_t2_t3'][$productId] ?? 0.0) + $quantity;
            }
        }

        return $buckets;
    }

    /**
     * @param  array<int>  $productIds
     * @return array<int, float>
     */
    private function latestStockSnapshots(int $companyId, array $productIds, CarbonImmutable $t0Date): array
    {
        if ($productIds === []) {
            return [];
        }

        $snapshots = StockSnapshot::query()
            ->select(['product_id', 'snapshot_date', 'free_stock'])
            ->where('company_id', $companyId)
            ->whereIn('product_id', $productIds)
            ->where('snapshot_date', '<', $t0Date->addDay()->toDateString())
            ->orderBy('product_id')
            ->orderByDesc('snapshot_date')
            ->get();

        $latest = [];

        foreach ($snapshots as $snapshot) {
            $productId = (int) $snapshot->product_id;

            if (! array_key_exists($productId, $latest)) {
                $latest[$productId] = (float) $snapshot->free_stock;
            }
        }

        return $latest;
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<string, CarbonImmutable>  $dates
     * @return array<string, array<int, float>>
     */
    private function inboundBuckets(int $companyId, int $supplierId, array $productIds, array $dates): array
    {
        $buckets = [
            'until_t1' => [],
            't1_t3' => [],
        ];

        if ($productIds === []) {
            return $buckets;
        }

        $inboundItems = InboundOrderItem::query()
            ->select([
                'id',
                'inbound_order_id',
                'product_id',
                'ordered_quantity',
                'confirmed_quantity',
                'received_quantity',
                'expected_arrival_date',
                'confirmed_arrival_date',
            ])
            ->with(['inboundOrder' => fn ($query) => $query->select([
                'id',
                'company_id',
                'supplier_id',
                'expected_arrival_date',
                'confirmed_arrival_date',
                'status',
            ])])
            ->whereIn('product_id', $productIds)
            ->whereHas('inboundOrder', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('supplier_id', $supplierId))
            ->get();

        foreach ($inboundItems as $inboundItem) {
            $inboundOrder = $inboundItem->inboundOrder;

            if ($inboundOrder === null || in_array($inboundOrder->status, ['cancelled', 'completed'], true)) {
                continue;
            }

            $arrivalDate = $inboundItem->confirmed_arrival_date
                ?? $inboundItem->expected_arrival_date
                ?? $inboundOrder->confirmed_arrival_date
                ?? $inboundOrder->expected_arrival_date;

            if ($arrivalDate === null) {
                continue;
            }

            $arrivalDate = CarbonImmutable::parse($arrivalDate);
            $productId = (int) $inboundItem->product_id;
            $quantity = max(0.0, (float) ($inboundItem->confirmed_quantity ?? $inboundItem->ordered_quantity) - (float) ($inboundItem->received_quantity ?? 0));

            if ($arrivalDate->lt($dates['t1_date'])) {
                $buckets['until_t1'][$productId] = ($buckets['until_t1'][$productId] ?? 0.0) + $quantity;
            } elseif ($this->dateInRange($arrivalDate, $dates['t1_date'], $dates['t3_date'])) {
                $buckets['t1_t3'][$productId] = ($buckets['t1_t3'][$productId] ?? 0.0) + $quantity;
            }
        }

        return $buckets;
    }

    /**
     * @param  array<int>  $productIds
     * @return array<int, float>
     */
    private function reservationBuckets(int $companyId, array $productIds, CarbonImmutable $t3Date): array
    {
        if ($productIds === []) {
            return [];
        }

        $reservations = Reservation::query()
            ->select(['product_id', 'quantity', 'expected_usage_date', 'status'])
            ->where('company_id', $companyId)
            ->whereIn('product_id', $productIds)
            ->get();

        $buckets = [];

        foreach ($reservations as $reservation) {
            if (in_array($reservation->status, ['cancelled', 'completed'], true)) {
                continue;
            }

            if ($reservation->expected_usage_date !== null && CarbonImmutable::parse($reservation->expected_usage_date)->gt($t3Date)) {
                continue;
            }

            $productId = (int) $reservation->product_id;
            $buckets[$productId] = ($buckets[$productId] ?? 0.0) + (float) $reservation->quantity;
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, CarbonImmutable>
     */
    private function dates(array $parameters): array
    {
        $t0Date = $this->date($parameters, 't0_date');
        $t1Date = $this->date($parameters, 't1_date');
        $t2Date = $this->date($parameters, 't2_date');
        $t3Date = $this->date($parameters, 't3_date');
        $currentTrendStartDate = $this->date($parameters, 'current_year_trend_start_date', $t0Date->subDays(90));
        $currentTrendEndDate = $this->date($parameters, 'current_year_trend_end_date', $t0Date);
        $lastTrendStartDate = $this->date($parameters, 'last_year_trend_start_date', $currentTrendStartDate->subYear());
        $lastTrendEndDate = $this->date($parameters, 'last_year_trend_end_date', $currentTrendEndDate->subYear());

        return [
            't0_date' => $t0Date,
            't1_date' => $t1Date,
            't2_date' => $t2Date,
            't3_date' => $t3Date,
            'current_trend_start_date' => $currentTrendStartDate,
            'current_trend_end_date' => $currentTrendEndDate,
            'last_trend_start_date' => $lastTrendStartDate,
            'last_trend_end_date' => $lastTrendEndDate,
            'last_year_t0_date' => $t0Date->subYear(),
            'last_year_t1_date' => $t1Date->subYear(),
            'last_year_t2_date' => $t2Date->subYear(),
            'last_year_t3_date' => $t3Date->subYear(),
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function date(array $parameters, string $key, ?CarbonImmutable $default = null): CarbonImmutable
    {
        if (isset($parameters[$key])) {
            return CarbonImmutable::parse($parameters[$key]);
        }

        if ($default !== null) {
            return $default;
        }

        throw new \InvalidArgumentException("Missing required date parameter [{$key}].");
    }

    /**
     * @param  array<int, CarbonImmutable>  $dates
     */
    private function minDate(array $dates): CarbonImmutable
    {
        $min = $dates[0];

        foreach ($dates as $date) {
            if ($date->lt($min)) {
                $min = $date;
            }
        }

        return $min;
    }

    /**
     * @param  array<int, CarbonImmutable>  $dates
     */
    private function maxDate(array $dates): CarbonImmutable
    {
        $max = $dates[0];

        foreach ($dates as $date) {
            if ($date->gt($max)) {
                $max = $date;
            }
        }

        return $max;
    }

    private function dateInRange(CarbonImmutable $date, CarbonImmutable $startDate, CarbonImmutable $endDate): bool
    {
        return $date->gte($startDate) && $date->lt($endDate);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function requiredInt(array $parameters, string $key): int
    {
        if (! isset($parameters[$key]) || ! is_numeric($parameters[$key])) {
            throw new \InvalidArgumentException("Missing required integer parameter [{$key}].");
        }

        return (int) $parameters[$key];
    }

    /**
     * @param  list<array<string, mixed>>  $calculations
     */
    private function calculationsRequireHumanReview(array $calculations): bool
    {
        foreach ($calculations as $calculation) {
            if ((bool) $calculation['result']['requires_human_review']) {
                return true;
            }
        }

        return false;
    }
}
