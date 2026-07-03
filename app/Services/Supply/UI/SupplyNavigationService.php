<?php

namespace App\Services\Supply\UI;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class SupplyNavigationService
{
    /**
     * @return list<array{label:string,items:list<array{label:string,route:string,href:string,active:bool,badge:int|null,description:string}>}>
     */
    public function navigation(?User $user = null): array
    {
        return collect($this->groups())
            ->map(fn (array $group): array => [
                'label' => $group['label'],
                'items' => collect($group['items'])
                    ->filter(fn (array $item): bool => $this->canShow($item, $user))
                    ->map(fn (array $item): array => $this->presentItem($item))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label:string,items:list<array{label:string,route:string,active:string,permission?:string,roles?:list<UserRole>,description?:string}>}>
     */
    private function groups(): array
    {
        return [
            [
                'label' => 'Supply',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'supply.dashboard', 'active' => 'supply.dashboard*', 'description' => 'Supply overview and action queue.'],
                    ['label' => 'Calculations', 'route' => 'supply.calculations.index', 'active' => 'supply.calculations.*', 'description' => 'Deterministic calculation runs.'],
                    ['label' => 'Forecasting', 'route' => 'supply.forecasting.scenarios.index', 'active' => 'supply.forecasting.*', 'description' => 'Deterministic refinement profiles, exclusions and scenarios.'],
                    ['label' => 'Order Proposals', 'route' => 'supply.proposals.index', 'active' => 'supply.proposals.*', 'description' => 'Review, approve and adjust proposed order quantities.'],
                    ['label' => 'Supplier Orders', 'route' => 'supply.supplier-orders.index', 'active' => 'supply.supplier-orders.*', 'description' => 'Supplier order exports and email workflow.'],
                ],
            ],
            [
                'label' => 'Communication',
                'items' => [
                    ['label' => 'Emails', 'route' => 'supply.emails.index', 'active' => 'supply.emails.*', 'description' => 'Inbound and outbound supplier email.'],
                    ['label' => 'AI Extractions', 'route' => 'supply.ai-extractions.index', 'active' => 'supply.ai-extractions.*', 'description' => 'AI suggestions awaiting human review.'],
                    ['label' => 'Form Autofill Runs', 'route' => 'supply.form-autofill-runs.index', 'active' => 'supply.form-autofill-runs.*', 'description' => 'Reviewed form suggestions and final values.'],
                    ['label' => 'Supplier Confirmations', 'route' => 'supply.supplier-confirmations.index', 'active' => 'supply.supplier-confirmations.*', 'description' => 'Applied supplier confirmations and discrepancies.'],
                ],
            ],
            [
                'label' => 'Transport & Logistics',
                'items' => [
                    ['label' => 'Carrier Quotes', 'route' => 'supply.transport.quotes.index', 'active' => 'supply.transport.*', 'description' => 'Quote comparison and user carrier selection.'],
                    ['label' => 'Carriers', 'route' => 'supply.carriers.index', 'active' => 'supply.carriers.*', 'description' => 'Carrier records and contacts.'],
                    ['label' => 'Logistics', 'route' => 'supply.logistics.index', 'active' => 'supply.logistics.*', 'description' => 'Delivery dates, receiving and delays.'],
                    ['label' => 'Notifications', 'route' => 'supply.notifications.index', 'active' => 'supply.notifications.*', 'description' => 'Database notifications and review alerts.'],
                ],
            ],
            [
                'label' => 'Data',
                'items' => [
                    ['label' => 'Products', 'route' => 'supply.products.index', 'active' => 'supply.products.*', 'description' => 'Product master data.'],
                    ['label' => 'Suppliers', 'route' => 'supply.suppliers.index', 'active' => 'supply.suppliers.*', 'description' => 'Supplier records and order contacts.'],
                    ['label' => 'Stock', 'route' => 'supply.stock.index', 'active' => 'supply.stock.*', 'description' => 'Stock snapshots.'],
                    ['label' => 'Sales History', 'route' => 'supply.sales-history.index', 'active' => 'supply.sales-history.*', 'description' => 'Sales history inputs.'],
                    ['label' => 'Inbound Orders', 'route' => 'supply.inbound-orders.index', 'active' => 'supply.inbound-orders.*', 'description' => 'Inbound supply.'],
                    ['label' => 'Reservations', 'route' => 'supply.reservations.index', 'active' => 'supply.reservations.*', 'description' => 'Reserved quantities.'],
                    ['label' => 'Imports', 'route' => 'supply.imports.index', 'active' => 'supply.imports.*', 'description' => 'Import batches and row validation.'],
                    ['label' => 'Exports', 'route' => 'supply.exports.index', 'active' => 'supply.exports.*', 'description' => 'Generated exports.'],
                ],
            ],
            [
                'label' => 'Pilot & Integrations',
                'items' => [
                    ['label' => 'Pilot UAT', 'route' => 'supply.pilots.index', 'active' => 'supply.pilots.*', 'description' => 'Pilot supplier onboarding and UAT.'],
                    ['label' => 'Form Templates', 'route' => 'supply.forms.templates.index', 'active' => 'supply.forms.templates.*', 'description' => 'Email and manufacturer form templates.'],
                    ['label' => 'Integrations', 'route' => 'supply.integrations.index', 'active' => 'supply.integrations.*', 'permission' => 'manage_integrations', 'description' => 'Approved provider configuration.'],
                    ['label' => 'Onboarding Checklist', 'route' => 'supply.onboarding.index', 'active' => 'supply.onboarding.*', 'description' => 'Real-data onboarding readiness.'],
                ],
            ],
            [
                'label' => 'Admin',
                'items' => [
                    ['label' => 'Audit Logs', 'route' => 'supply.audit-logs.index', 'active' => 'supply.audit-logs.*', 'permission' => 'view_audit_logs', 'description' => 'Critical action audit trail.'],
                    ['label' => 'Settings', 'route' => 'supply.settings.index', 'active' => 'supply.settings.*', 'permission' => 'manage_settings', 'description' => 'Supply agent settings.'],
                    ['label' => 'Health Check', 'route' => 'supply.health.index', 'active' => 'supply.health.*', 'permission' => 'manage_settings', 'description' => 'System health and readiness.'],
                ],
            ],
        ];
    }

    /**
     * @param  array{label:string,route:string,active:string,permission?:string,roles?:list<UserRole>,description?:string}  $item
     */
    private function canShow(array $item, ?User $user): bool
    {
        if (! Route::has($item['route'])) {
            return false;
        }

        if ($user === null) {
            return ! isset($item['permission']);
        }

        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if (isset($item['permission'])) {
            return $user->hasPermissionTo($item['permission']);
        }

        return true;
    }

    /**
     * @param  array{label:string,route:string,active:string,permission?:string,roles?:list<UserRole>,description?:string}  $item
     * @return array{label:string,route:string,href:string,active:bool,badge:int|null,description:string}
     */
    private function presentItem(array $item): array
    {
        return [
            'label' => $item['label'],
            'route' => $item['route'],
            'href' => route($item['route']),
            'active' => request()->routeIs($item['active']),
            'badge' => null,
            'description' => $item['description'] ?? '',
        ];
    }
}
