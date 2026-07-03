<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use Database\Factories\OperationalIncidentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalIncident extends Model
{
    /** @use HasFactory<OperationalIncidentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'incident_number',
        'incident_type',
        'severity',
        'priority',
        'status',
        'title',
        'description',
        'source_type',
        'source_id',
        'source_label',
        'source_url',
        'assigned_user_id',
        'reported_by_user_id',
        'first_response_at',
        'response_due_at',
        'resolution_due_at',
        'resolved_at',
        'closed_at',
        'sla_status',
        'root_cause_category',
        'root_cause_summary',
        'resolution_note',
        'prevention_notes',
        'corrective_action_required',
        'no_action_required_reason',
        'occurrence_count',
        'last_seen_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'incident_type' => IncidentType::class,
            'severity' => IncidentSeverity::class,
            'priority' => IncidentPriority::class,
            'status' => IncidentStatus::class,
            'sla_status' => IncidentSlaStatus::class,
            'first_response_at' => 'datetime',
            'response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'corrective_action_required' => 'boolean',
            'last_seen_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OperationalIncidentEvent::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OperationalIncidentComment::class);
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(IncidentCorrectiveAction::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(IncidentEscalation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', IncidentStatus::activeValues());
    }

    protected function incidentTypeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->labelFor($this->incident_type));
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->labelFor($this->status));
    }

    protected function severityLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->labelFor($this->severity));
    }

    protected function priorityLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->labelFor($this->priority));
    }

    protected function slaStatusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->labelFor($this->sla_status ?? IncidentSlaStatus::WithinSla));
    }

    protected function rootCauseCategoryLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->root_cause_category === null ? 'Not set' : $this->labelFor($this->root_cause_category));
    }

    protected function sourceTypeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->source_type === null ? 'Manual' : $this->labelFor($this->source_type));
    }

    protected function statusTone(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            IncidentStatus::Closed, IncidentStatus::Resolved => 'success',
            IncidentStatus::Cancelled => 'neutral',
            IncidentStatus::WaitingOnUser, IncidentStatus::WaitingOnSupplier, IncidentStatus::WaitingOnExternal => 'warning',
            IncidentStatus::InProgress, IncidentStatus::Triaged => 'info',
            default => 'error',
        });
    }

    protected function severityTone(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->severity) {
            IncidentSeverity::Critical => 'error',
            IncidentSeverity::High => 'warning',
            IncidentSeverity::Medium => 'info',
            default => 'neutral',
        });
    }

    protected function slaTone(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->sla_status) {
            IncidentSlaStatus::ResponseBreached, IncidentSlaStatus::ResolutionBreached, IncidentSlaStatus::CompletedBreached => 'error',
            default => 'success',
        });
    }

    private function labelFor(mixed $value): string
    {
        $value = $value instanceof \BackedEnum ? $value->value : (string) $value;

        return ucwords(strtr($value, '_', ' '));
    }
}
