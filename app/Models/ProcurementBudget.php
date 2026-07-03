<?php

namespace App\Models;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use Database\Factories\ProcurementBudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementBudget extends Model
{
    /** @use HasFactory<ProcurementBudgetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'period_type',
        'date_from',
        'date_to',
        'currency',
        'total_amount',
        'status',
        'owner_user_id',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => BudgetPeriodType::class,
            'date_from' => 'date',
            'date_to' => 'date',
            'total_amount' => 'decimal:4',
            'status' => BudgetStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementBudgetLine::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::Active->value);
    }
}
