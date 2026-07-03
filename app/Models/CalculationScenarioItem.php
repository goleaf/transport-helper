<?php

namespace App\Models;

use Database\Factories\CalculationScenarioItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CalculationScenarioItem extends Model
{
    /** @use HasFactory<CalculationScenarioItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'calculation_scenario_id',
        'product_id',
        'base_order_proposal_item_id',
        'status',
        'base_raw_need',
        'base_recommended_quantity',
        'simulated_raw_need',
        'simulated_recommended_quantity',
        'difference_quantity',
        'trend_used',
        'seasonality_factor',
        'manual_trend_override_id',
        'applied_profile_id',
        'input_json',
        'output_json',
        'explanation_json',
        'warnings_json',
        'requires_human_review',
    ];

    protected function casts(): array
    {
        return [
            'base_raw_need' => 'decimal:4',
            'base_recommended_quantity' => 'decimal:4',
            'simulated_raw_need' => 'decimal:4',
            'simulated_recommended_quantity' => 'decimal:4',
            'difference_quantity' => 'decimal:4',
            'trend_used' => 'decimal:6',
            'seasonality_factor' => 'decimal:6',
            'input_json' => 'array',
            'output_json' => 'array',
            'explanation_json' => 'array',
            'warnings_json' => 'array',
            'requires_human_review' => 'boolean',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(CalculationScenario::class, 'calculation_scenario_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function baseOrderProposalItem(): BelongsTo
    {
        return $this->belongsTo(OrderProposalItem::class, 'base_order_proposal_item_id');
    }

    public function manualTrendOverride(): BelongsTo
    {
        return $this->belongsTo(TrendOverride::class, 'manual_trend_override_id');
    }

    public function appliedProfile(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentProfile::class, 'applied_profile_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
