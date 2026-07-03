<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerFormTemplateFile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form_template_id',
        'supplier_id',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
        'checksum',
        'version',
        'mapping_json',
        'validation_rules_json',
        'is_active',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'mapping_json' => 'array',
            'validation_rules_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
