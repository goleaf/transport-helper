<?php

namespace App\Models;

use Database\Factories\SalesHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesHistory extends Model
{
    /** @use HasFactory<SalesHistoryFactory> */
    use HasFactory;

    protected $table = 'sales_history';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'sales_date',
        'quantity',
        'channel',
        'customer_id',
        'is_promotion',
        'is_anomaly',
        'anomaly_reason',
        'source_type',
        'source_reference',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'sales_date' => 'date',
            'quantity' => 'decimal:3',
            'is_promotion' => 'boolean',
            'is_anomaly' => 'boolean',
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
