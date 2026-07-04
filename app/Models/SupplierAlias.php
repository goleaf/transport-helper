<?php

namespace App\Models;

use App\Enums\MasterDataAliasStatus;
use Database\Factories\SupplierAliasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAlias extends Model
{
    /** @use HasFactory<SupplierAliasFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'alias',
        'alias_type',
        'source_type',
        'source_reference',
        'status',
        'confidence',
        'reason',
        'approved_by_user_id',
        'approved_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MasterDataAliasStatus::class,
            'confidence' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MasterDataAliasStatus::Active->value);
    }
}
