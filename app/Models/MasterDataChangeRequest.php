<?php

namespace App\Models;

use App\Enums\MasterDataChangeRequestStatus;
use App\Enums\MasterDataChangeRequestType;
use Database\Factories\MasterDataChangeRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MasterDataChangeRequest extends Model
{
    /** @use HasFactory<MasterDataChangeRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'request_type',
        'status',
        'requested_by_user_id',
        'approved_by_user_id',
        'rejected_by_user_id',
        'applied_by_user_id',
        'related_model_type',
        'related_model_id',
        'requested_changes_json',
        'reason',
        'approval_note',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => MasterDataChangeRequestType::class,
            'status' => MasterDataChangeRequestStatus::class,
            'requested_changes_json' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }
}
