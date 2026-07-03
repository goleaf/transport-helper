<?php

namespace App\Models;

use App\Enums\ProcurementApprovalRequestStatus;
use App\Enums\ProcurementExceptionType;
use Database\Factories\ProcurementExceptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementException extends Model
{
    /** @use HasFactory<ProcurementExceptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'exception_type',
        'exceptable_type',
        'exceptable_id',
        'status',
        'reason',
        'requested_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'exception_type' => ProcurementExceptionType::class,
            'status' => ProcurementApprovalRequestStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function exceptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ProcurementApprovalRequestStatus::Approved->value);
    }
}
