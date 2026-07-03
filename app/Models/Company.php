<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'timezone',
        'default_currency',
    ];

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }

    public function salesHistory(): HasMany
    {
        return $this->hasMany(SalesHistory::class);
    }

    public function inboundOrders(): HasMany
    {
        return $this->hasMany(InboundOrder::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function calculationRuns(): HasMany
    {
        return $this->hasMany(CalculationRun::class);
    }

    public function replenishmentProfiles(): HasMany
    {
        return $this->hasMany(ReplenishmentProfile::class);
    }

    public function salesExclusionRules(): HasMany
    {
        return $this->hasMany(SalesExclusionRule::class);
    }

    public function trendOverrides(): HasMany
    {
        return $this->hasMany(TrendOverride::class);
    }

    public function calculationScenarios(): HasMany
    {
        return $this->hasMany(CalculationScenario::class);
    }

    public function orderProposals(): HasMany
    {
        return $this->hasMany(OrderProposal::class);
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class);
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function supplierConfirmations(): HasMany
    {
        return $this->hasMany(SupplierConfirmation::class);
    }

    public function carriers(): HasMany
    {
        return $this->hasMany(Carrier::class);
    }

    public function carrierQuotes(): HasMany
    {
        return $this->hasMany(CarrierQuote::class);
    }

    public function logisticsRecords(): HasMany
    {
        return $this->hasMany(LogisticsRecord::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function exportFiles(): HasMany
    {
        return $this->hasMany(ExportFile::class);
    }

    public function integrationConnections(): HasMany
    {
        return $this->hasMany(IntegrationConnection::class);
    }

    public function appSettings(): HasMany
    {
        return $this->hasMany(AppSetting::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function savedViews(): HasMany
    {
        return $this->hasMany(SavedView::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function formAutofillRuns(): HasMany
    {
        return $this->hasMany(FormAutofillRun::class);
    }
}
