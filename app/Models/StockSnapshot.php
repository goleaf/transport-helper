<?php

namespace App\Models;

use Database\Factories\StockSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSnapshot extends Model
{
    /** @use HasFactory<StockSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'snapshot_date',
        'free_stock',
        'total_stock',
        'reserved_quantity',
        'damaged_quantity',
        'inactive_quantity',
        'in_transit_quantity',
        'source_type',
        'source_reference',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'free_stock' => 'decimal:3',
            'total_stock' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'damaged_quantity' => 'decimal:3',
            'inactive_quantity' => 'decimal:3',
            'in_transit_quantity' => 'decimal:3',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
