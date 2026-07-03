<?php

namespace App\Models;

use App\Enums\CorrectiveActionStatus;
use Database\Factories\IncidentCorrectiveActionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentCorrectiveAction extends Model
{
    /** @use HasFactory<IncidentCorrectiveActionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'operational_incident_id',
        'title',
        'description',
        'owner_user_id',
        'due_date',
        'status',
        'completion_note',
        'completed_at',
        'verified_by_user_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CorrectiveActionStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(OperationalIncident::class, 'operational_incident_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $value = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

            return ucwords(strtr($value, '_', ' '));
        });
    }
}
