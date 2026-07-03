<?php

namespace App\Models;

use Database\Factories\ImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImportRow extends Model
{
    /** @use HasFactory<ImportRowFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_json',
        'normalized_json',
        'status',
        'error_message',
        'related_model_type',
        'related_model_id',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'raw_json' => 'array',
            'normalized_json' => 'array',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }
}
