<?php

namespace App\Models;

use App\Enums\CalculationScenarioStatus;
use App\Enums\ScenarioSimulationMode;
use Database\Factories\CalculationScenarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CalculationScenario extends Model
{
    /** @use HasFactory<CalculationScenarioFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'base_calculation_run_id',
        'name',
        'status',
        'simulation_mode',
        'formula_version',
        'parameters_json',
        'profile_snapshot_json',
        'summary_json',
        'warnings_json',
        'errors_json',
        'created_by_user_id',
        'simulated_at',
        'converted_order_proposal_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CalculationScenarioStatus::class,
            'simulation_mode' => ScenarioSimulationMode::class,
            'parameters_json' => 'array',
            'profile_snapshot_json' => 'array',
            'summary_json' => 'array',
            'warnings_json' => 'array',
            'errors_json' => 'array',
            'simulated_at' => 'datetime',
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

    public function baseCalculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class, 'base_calculation_run_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CalculationScenarioItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function convertedOrderProposal(): BelongsTo
    {
        return $this->belongsTo(OrderProposal::class, 'converted_order_proposal_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
