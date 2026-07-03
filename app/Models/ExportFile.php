<?php

namespace App\Models;

use Database\Factories\ExportFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExportFile extends Model
{
    /** @use HasFactory<ExportFileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'export_type',
        'related_model_type',
        'related_model_id',
        'filename',
        'stored_path',
        'mime_type',
        'status',
        'created_by_user_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }
}
