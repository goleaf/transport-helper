<?php

namespace App\Models;

use App\Enums\TrendOverrideStatus;
use Database\Factories\TrendOverrideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TrendOverride extends Model
{
    /** @use HasFactory<TrendOverrideFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'product_id',
        'category',
        'trend_value',
        'date_from',
        'date_to',
        'status',
        'reason',
        'approval_note',
        'rejection_reason',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'revoked_by_user_id',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'trend_value' => 'decimal:6',
            'date_from' => 'date',
            'date_to' => 'date',
            'status' => TrendOverrideStatus::class,
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeApprovedActive(Builder $query, Company|int $company, string $date): Builder
    {
        return $query
            ->where('company_id', $company instanceof Company ? $company->getKey() : $company)
            ->where('status', TrendOverrideStatus::Approved)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date);
    }
}
