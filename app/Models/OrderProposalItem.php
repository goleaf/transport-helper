<?php

namespace App\Models;

use App\Enums\OrderProposalItemStatus;
use Database\Factories\OrderProposalItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProposalItem extends Model
{
    /** @use HasFactory<OrderProposalItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_proposal_id',
        'product_id',
        't0_date',
        't1_date',
        't2_date',
        't3_date',
        'trend',
        'need_t0_t1',
        'stock_t1',
        'need_t1_t2',
        'safety_stock',
        'inbound_until_t1',
        'inbound_t1_t3',
        'reserved_quantity',
        'raw_need',
        'moq_applied',
        'pack_multiple_applied',
        'pallet_quantity_applied',
        'recommended_quantity',
        'approved_quantity',
        'user_adjusted_quantity',
        'adjustment_reason',
        'explanation_json',
        'warnings_json',
        'requires_human_review',
        'status',
    ];

    protected function casts(): array
    {
        return [
            't0_date' => 'date',
            't1_date' => 'date',
            't2_date' => 'date',
            't3_date' => 'date',
            'trend' => 'decimal:3',
            'need_t0_t1' => 'decimal:3',
            'stock_t1' => 'decimal:3',
            'need_t1_t2' => 'decimal:3',
            'safety_stock' => 'decimal:3',
            'inbound_until_t1' => 'decimal:3',
            'inbound_t1_t3' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'raw_need' => 'decimal:3',
            'moq_applied' => 'decimal:3',
            'pack_multiple_applied' => 'decimal:3',
            'pallet_quantity_applied' => 'decimal:3',
            'recommended_quantity' => 'decimal:3',
            'approved_quantity' => 'decimal:3',
            'user_adjusted_quantity' => 'decimal:3',
            'explanation_json' => 'array',
            'warnings_json' => 'array',
            'requires_human_review' => 'boolean',
            'status' => OrderProposalItemStatus::class,
        ];
    }

    public function orderProposal(): BelongsTo
    {
        return $this->belongsTo(OrderProposal::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('status', OrderProposalItemStatus::NeedsReview->value);
    }
}
