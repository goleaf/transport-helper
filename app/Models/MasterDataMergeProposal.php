<?php

namespace App\Models;

use App\Enums\MasterDataMergeStatus;
use Database\Factories\MasterDataMergeProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MasterDataMergeProposal extends Model
{
    /** @use HasFactory<MasterDataMergeProposalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'merge_type',
        'source_model_type',
        'source_model_id',
        'target_model_type',
        'target_model_id',
        'status',
        'reason',
        'impact_json',
        'proposed_by_user_id',
        'approved_by_user_id',
        'rejected_by_user_id',
        'executed_by_user_id',
        'approved_at',
        'rejected_at',
        'executed_at',
        'rejection_reason',
        'execution_result_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => MasterDataMergeStatus::class,
            'impact_json' => 'array',
            'execution_result_json' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceModel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_model_type', 'source_model_id');
    }

    public function targetModel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_model_type', 'target_model_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }
}
