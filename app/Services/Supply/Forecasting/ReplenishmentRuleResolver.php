<?php

namespace App\Services\Supply\Forecasting;

use App\Models\Company;
use App\Models\Product;
use App\Models\ReplenishmentProfile;
use App\Models\Supplier;

class ReplenishmentRuleResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{profile: ReplenishmentProfile|null, rules: array<string, mixed>, explanation: list<array<string, mixed>>, warnings: list<string>}
     */
    public function resolve(Company $company, Product $product, ?Supplier $supplier = null, array $context = []): array
    {
        $supplierId = $supplier?->getKey();

        $profile = ReplenishmentProfile::query()
            ->select([
                'id',
                'company_id',
                'supplier_id',
                'product_id',
                'category',
                'name',
                'status',
                'priority',
                'lead_time_days_override',
                'safety_days_override',
                'safety_stock_multiplier',
                'seasonality_enabled',
                'seasonality_mode',
                'exclude_promotions',
                'exclude_anomalies',
                'outlier_detection_enabled',
                'outlier_multiplier',
                'reservation_strategy',
                'pallet_strategy',
                'transport_strategy',
                'strategic_minimum_order_enabled',
                'config_json',
                'notes',
                'is_active',
            ])
            ->activeForCompany($company)
            ->where(function ($query) use ($product): void {
                $query->whereNull('product_id')->orWhere('product_id', $product->getKey());
            })
            ->where(function ($query) use ($product): void {
                $query->whereNull('category')->orWhere('category', $product->category);
            })
            ->where(function ($query) use ($supplierId): void {
                $query->whereNull('supplier_id');

                if ($supplierId !== null) {
                    $query->orWhere('supplier_id', $supplierId);
                }
            })
            ->limit(200)
            ->get()
            ->sort(function (ReplenishmentProfile $left, ReplenishmentProfile $right) use ($product, $supplier): int {
                $specificity = $this->specificity($right, $product, $supplier) <=> $this->specificity($left, $product, $supplier);

                if ($specificity !== 0) {
                    return $specificity;
                }

                $priority = ((int) $left->priority) <=> ((int) $right->priority);

                return $priority !== 0 ? $priority : ((int) $right->getKey() <=> (int) $left->getKey());
            })
            ->first();

        $defaults = $this->safeDefaults($supplier);
        $rules = $profile instanceof ReplenishmentProfile ? $this->rulesFromProfile($profile, $defaults) : $defaults;

        return [
            'profile' => $profile,
            'rules' => $rules,
            'explanation' => [
                [
                    'rule' => 'resolution_priority',
                    'value' => [
                        'product_specific',
                        'supplier_product',
                        'supplier_category',
                        'category',
                        'supplier',
                        'company_default',
                        'safe_defaults',
                    ],
                ],
                [
                    'rule' => $profile instanceof ReplenishmentProfile ? 'profile_selected' : 'safe_defaults_selected',
                    'profile_id' => $profile?->getKey(),
                    'profile_name' => $profile?->name,
                    'specificity' => $profile instanceof ReplenishmentProfile ? $this->specificity($profile, $product, $supplier) : 0,
                ],
            ],
            'warnings' => $profile instanceof ReplenishmentProfile ? [] : ['no_active_replenishment_profile_matched'],
        ];
    }

    private function specificity(ReplenishmentProfile $profile, Product $product, ?Supplier $supplier): int
    {
        if ($profile->supplier_id !== null && $profile->product_id !== null && (int) $profile->supplier_id === (int) $supplier?->getKey() && (int) $profile->product_id === (int) $product->getKey()) {
            return 100;
        }

        if ($profile->product_id !== null && (int) $profile->product_id === (int) $product->getKey()) {
            return 95;
        }

        if ($profile->supplier_id !== null && $profile->category !== null && (int) $profile->supplier_id === (int) $supplier?->getKey() && $profile->category === $product->category) {
            return 80;
        }

        if ($profile->category !== null && $profile->category === $product->category) {
            return 60;
        }

        if ($profile->supplier_id !== null && (int) $profile->supplier_id === (int) $supplier?->getKey()) {
            return 40;
        }

        return 10;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeDefaults(?Supplier $supplier): array
    {
        return [
            'safety_days_override' => 14,
            'lead_time_days_override' => $supplier?->default_lead_time_days ?? 21,
            'safety_stock_multiplier' => 1.0,
            'exclude_promotions' => true,
            'exclude_anomalies' => true,
            'seasonality_enabled' => false,
            'seasonality_mode' => 'none',
            'outlier_detection_enabled' => false,
            'outlier_multiplier' => (float) config('supply.forecasting.outliers.default_multiplier', 3.0),
            'reservation_strategy' => 'reserved_not_removed_from_free_stock',
            'pallet_strategy' => 'show_only',
            'transport_strategy' => 'show_only',
            'strategic_minimum_order_enabled' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function rulesFromProfile(ReplenishmentProfile $profile, array $defaults): array
    {
        return [
            'safety_days_override' => $profile->safety_days_override ?? $defaults['safety_days_override'],
            'lead_time_days_override' => $profile->lead_time_days_override ?? $defaults['lead_time_days_override'],
            'safety_stock_multiplier' => $profile->safety_stock_multiplier !== null ? (float) $profile->safety_stock_multiplier : $defaults['safety_stock_multiplier'],
            'exclude_promotions' => (bool) $profile->exclude_promotions,
            'exclude_anomalies' => (bool) $profile->exclude_anomalies,
            'seasonality_enabled' => (bool) $profile->seasonality_enabled,
            'seasonality_mode' => $profile->seasonality_mode ?: $defaults['seasonality_mode'],
            'outlier_detection_enabled' => (bool) $profile->outlier_detection_enabled,
            'outlier_multiplier' => $profile->outlier_multiplier !== null ? (float) $profile->outlier_multiplier : $defaults['outlier_multiplier'],
            'reservation_strategy' => $profile->reservation_strategy ?: $defaults['reservation_strategy'],
            'pallet_strategy' => $profile->pallet_strategy ?: $defaults['pallet_strategy'],
            'transport_strategy' => $profile->transport_strategy ?: $defaults['transport_strategy'],
            'strategic_minimum_order_enabled' => (bool) $profile->strategic_minimum_order_enabled,
        ];
    }
}
