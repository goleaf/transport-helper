<?php

namespace App\Models;

use App\Enums\ImportBatchStatus;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'import_type',
        'source_type',
        'source_name',
        'adapter',
        'original_filename',
        'checksum',
        'status',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'started_by_user_id',
        'started_at',
        'finished_at',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'successful_rows' => 'integer',
            'failed_rows' => 'integer',
            'status' => ImportBatchStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }

    public function salesHistory(): HasMany
    {
        return $this->hasMany(SalesHistory::class);
    }
}
