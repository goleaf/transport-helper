<?php

namespace App\Models;

use Database\Factories\CalculationRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalculationRun extends Model
{
    /** @use HasFactory<CalculationRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'supplier_id',
        'calculation_date',
        'formula_version',
        'parameters_json',
        'status',
        'started_by_user_id',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'calculation_date' => 'date',
            'parameters_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function orderProposals(): HasMany
    {
        return $this->hasMany(OrderProposal::class);
    }
}
