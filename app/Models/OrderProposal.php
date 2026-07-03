<?php

namespace App\Models;

use App\Enums\OrderProposalStatus;
use Database\Factories\OrderProposalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderProposal extends Model
{
    /** @use HasFactory<OrderProposalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'calculation_run_id',
        'supplier_id',
        'status',
        'total_lines',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderProposalStatus::class,
            'total_lines' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderProposalItem::class);
    }

    public function supplierOrder(): HasOne
    {
        return $this->hasOne(SupplierOrder::class);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('status', OrderProposalStatus::NeedsReview->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', OrderProposalStatus::Approved->value);
    }
}
