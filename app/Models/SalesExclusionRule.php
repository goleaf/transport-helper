<?php

namespace App\Models;

use App\Enums\SalesExclusionRuleType;
use Database\Factories\SalesExclusionRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesExclusionRule extends Model
{
    /** @use HasFactory<SalesExclusionRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'product_id',
        'category',
        'rule_type',
        'date_from',
        'date_to',
        'applies_to',
        'reason',
        'is_active',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => SalesExclusionRuleType::class,
            'date_from' => 'date',
            'date_to' => 'date',
            'is_active' => 'boolean',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeActiveForCompany(Builder $query, Company|int $company): Builder
    {
        return $query
            ->where('company_id', $company instanceof Company ? $company->getKey() : $company)
            ->where('is_active', true);
    }
}
