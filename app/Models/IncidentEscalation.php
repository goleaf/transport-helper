<?php

namespace App\Models;

use App\Enums\EscalationStatus;
use Database\Factories\IncidentEscalationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentEscalation extends Model
{
    /** @use HasFactory<IncidentEscalationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'operational_incident_id',
        'escalation_level',
        'escalated_to_user_id',
        'escalated_by_user_id',
        'reason',
        'status',
        'escalated_at',
        'resolved_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => EscalationStatus::class,
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(OperationalIncident::class, 'operational_incident_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_user_id');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $value = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

            return ucwords(strtr($value, '_', ' '));
        });
    }
}
