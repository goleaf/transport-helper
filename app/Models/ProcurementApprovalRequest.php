<?php

namespace App\Models;

use App\Enums\ProcurementApprovalRequestStatus;
use Database\Factories\ProcurementApprovalRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementApprovalRequest extends Model
{
    /** @use HasFactory<ProcurementApprovalRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'approvable_type',
        'approvable_id',
        'status',
        'requested_by_user_id',
        'required_role',
        'required_permission',
        'amount',
        'currency',
        'reason',
        'metadata_json',
        'expires_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcurementApprovalRequestStatus::class,
            'amount' => 'decimal:4',
            'metadata_json' => 'array',
            'expires_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ProcurementApprovalDecision::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ProcurementApprovalRequestStatus::Pending->value);
    }
}
