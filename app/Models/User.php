<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function calculationRuns(): HasMany
    {
        return $this->hasMany(CalculationRun::class, 'started_by_user_id');
    }

    public function createdOrderProposals(): HasMany
    {
        return $this->hasMany(OrderProposal::class, 'created_by_user_id');
    }

    public function approvedOrderProposals(): HasMany
    {
        return $this->hasMany(OrderProposal::class, 'approved_by_user_id');
    }

    public function approvedSupplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class, 'approved_by_user_id');
    }

    public function sentSupplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class, 'sent_by_user_id');
    }

    public function reviewedAiEmailExtractions(): HasMany
    {
        return $this->hasMany(AiEmailExtraction::class, 'reviewed_by_user_id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class, 'started_by_user_id');
    }

    public function exportFiles(): HasMany
    {
        return $this->hasMany(ExportFile::class, 'created_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdFormAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class, 'created_by_user_id');
    }

    public function reviewedFormAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class, 'reviewed_by_user_id');
    }

    public function appliedFormAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class, 'applied_by_user_id');
    }

    public function acceptedFormAutofillFieldValues(): HasMany
    {
        return $this->hasMany(FormAutofillFieldValue::class, 'accepted_by_user_id');
    }

    public function formAutofillOutputs(): HasMany
    {
        return $this->hasMany(FormAutofillOutput::class, 'created_by_user_id');
    }

    public function hasRole(string|UserRole $role): bool
    {
        $roleName = $role instanceof UserRole ? $role->value : $role;

        if ($this->role instanceof UserRole && $this->role->value === $roleName) {
            return true;
        }

        return $this->roles->contains('name', $roleName);
    }

    /**
     * @param  list<string|UserRole>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    public function canManageSupplyWorkflow(): bool
    {
        return $this->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || ($this->role instanceof UserRole && $this->role->canManageSupply());
    }

    public function canManageLogisticsWorkflow(): bool
    {
        return $this->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager])
            || ($this->role instanceof UserRole && $this->role->canManageLogistics());
    }
}
