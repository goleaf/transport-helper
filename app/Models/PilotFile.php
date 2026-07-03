<?php

namespace App\Models;

use Database\Factories\PilotFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotFile extends Model
{
    /** @use HasFactory<PilotFileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pilot_supplier_id',
        'file_type',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
        'checksum',
        'metadata_json',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function pilotSupplier(): BelongsTo
    {
        return $this->belongsTo(PilotSupplier::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
