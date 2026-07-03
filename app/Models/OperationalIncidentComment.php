<?php

namespace App\Models;

use Database\Factories\OperationalIncidentCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalIncidentComment extends Model
{
    /** @use HasFactory<OperationalIncidentCommentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'operational_incident_id',
        'user_id',
        'comment',
        'is_internal',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(OperationalIncident::class, 'operational_incident_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
