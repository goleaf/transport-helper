<?php

namespace App\Models;

use App\Enums\ProcurementEnforcementMode;
use App\Enums\ProcurementPolicyStatus;
use Database\Factories\ProcurementPolicyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementPolicy extends Model
{
    /** @use HasFactory<ProcurementPolicyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'status',
        'enforcement_mode',
        'default_currency',
        'rules_json',
        'approval_thresholds_json',
        'supplier_rules_json',
        'budget_rules_json',
        'is_default',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcurementPolicyStatus::class,
            'enforcement_mode' => ProcurementEnforcementMode::class,
            'rules_json' => 'array',
            'approval_thresholds_json' => 'array',
            'supplier_rules_json' => 'array',
            'budget_rules_json' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProcurementPolicyStatus::Active->value);
    }
}
