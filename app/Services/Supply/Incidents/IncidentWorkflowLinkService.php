<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSourceType;
use App\Models\OperationalIncident;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class IncidentWorkflowLinkService
{
    public function sourceUrl(string $sourceType, ?int $sourceId): ?string
    {
        if ($sourceId === null) {
            return null;
        }

        $route = match ($sourceType) {
            IncidentSourceType::ImportBatch->value => 'supply.imports.show',
            IncidentSourceType::OrderProposal->value => 'supply.proposals.show',
            IncidentSourceType::SupplierOrder->value => 'supply.supplier-orders.show',
            IncidentSourceType::EmailMessage->value => 'supply.emails.show',
            IncidentSourceType::AiEmailExtraction->value => 'supply.ai-extractions.show',
            IncidentSourceType::FormAutofillRun->value => 'supply.form-autofill-runs.show',
            IncidentSourceType::SupplierConfirmation->value => 'supply.supplier-confirmations.show',
            IncidentSourceType::CarrierQuote->value => 'supply.transport.quotes.show',
            IncidentSourceType::LogisticsRecord->value => 'supply.logistics.show',
            default => null,
        };

        return $route !== null && Route::has($route) ? route($route, $sourceId, false) : null;
    }

    public function sourceLabel(string $sourceType, ?int $sourceId): ?string
    {
        if ($sourceId === null) {
            return null;
        }

        return str($sourceType)->replace('_', ' ')->title().' #'.$sourceId;
    }

    /**
     * @return array<int, mixed>
     */
    public function relatedIncidents(Model $model): array
    {
        return OperationalIncident::query()
            ->select(['id', 'incident_number', 'incident_type', 'severity', 'status', 'title'])
            ->where('source_id', $model->getKey())
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->all();
    }
}
