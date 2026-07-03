<?php

namespace App\Models;

use Database\Factories\ProcurementBudgetLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementBudgetLine extends Model
{
    /** @use HasFactory<ProcurementBudgetLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'procurement_budget_id',
        'supplier_id',
        'product_id',
        'category',
        'project_name',
        'manager_name',
        'amount',
        'committed_amount',
        'spent_amount',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'committed_amount' => 'decimal:4',
            'spent_amount' => 'decimal:4',
            'metadata_json' => 'array',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProcurementBudget::class, 'procurement_budget_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
