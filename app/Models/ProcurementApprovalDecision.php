<?php

namespace App\Models;

use App\Enums\ProcurementApprovalDecisionType;
use Database\Factories\ProcurementApprovalDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementApprovalDecision extends Model
{
    /** @use HasFactory<ProcurementApprovalDecisionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'procurement_approval_request_id',
        'decision',
        'decision_by_user_id',
        'note',
        'metadata_json',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ProcurementApprovalDecisionType::class,
            'metadata_json' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementApprovalRequest::class, 'procurement_approval_request_id');
    }

    public function decisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_user_id');
    }
}
