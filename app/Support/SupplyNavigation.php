<?php

namespace App\Support;

class SupplyNavigation
{
    /**
     * @return list<array{key:string,label:string,route:string,active:string,fragment?:string,children?:list<array{label:string,route:string,fragment:string}>}>
     */
    public static function items(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Supply Dashboard',
                'route' => 'supply.dashboard',
                'active' => 'supply.dashboard',
                'children' => [
                    ['label' => 'Replenishment priorities', 'route' => 'supply.dashboard', 'fragment' => 'replenishment-priorities'],
                    ['label' => 'Latest calculation runs', 'route' => 'supply.dashboard', 'fragment' => 'latest-calculation-runs'],
                    ['label' => 'Proposals needing review', 'route' => 'supply.dashboard', 'fragment' => 'proposals-needing-review'],
                    ['label' => 'Supplier orders awaiting action', 'route' => 'supply.dashboard', 'fragment' => 'supplier-orders-awaiting-action'],
                    ['label' => 'Emails needing review', 'route' => 'supply.dashboard', 'fragment' => 'emails-needing-review'],
                    ['label' => 'Form autofill runs needing review', 'route' => 'supply.dashboard', 'fragment' => 'form-autofill-runs-needing-review'],
                    ['label' => 'Logistics delays', 'route' => 'supply.dashboard', 'fragment' => 'logistics-delays'],
                ],
            ],
            ['key' => 'products', 'label' => 'Products', 'route' => 'supply.products.index', 'active' => 'supply.products.*'],
            ['key' => 'suppliers', 'label' => 'Suppliers', 'route' => 'supply.suppliers.index', 'active' => 'supply.suppliers.*'],
            ['key' => 'stock', 'label' => 'Stock', 'route' => 'supply.stock.index', 'active' => 'supply.stock.*'],
            ['key' => 'sales-history', 'label' => 'Sales History', 'route' => 'supply.sales-history.index', 'active' => 'supply.sales-history.*'],
            ['key' => 'inbound-orders', 'label' => 'Inbound Orders', 'route' => 'supply.inbound-orders.index', 'active' => 'supply.inbound-orders.*'],
            ['key' => 'reservations', 'label' => 'Reservations', 'route' => 'supply.reservations.index', 'active' => 'supply.reservations.*'],
            ['key' => 'calculations', 'label' => 'Calculations', 'route' => 'supply.calculations.index', 'active' => 'supply.calculations.*'],
            ['key' => 'order-proposals', 'label' => 'Order Proposals', 'route' => 'supply.proposals.index', 'active' => 'supply.proposals.*'],
            ['key' => 'supplier-orders', 'label' => 'Supplier Orders', 'route' => 'supply.supplier-orders.index', 'active' => 'supply.supplier-orders.*'],
            ['key' => 'emails', 'label' => 'Emails', 'route' => 'supply.emails.index', 'active' => 'supply.emails.*'],
            ['key' => 'ai-extractions', 'label' => 'AI Extractions', 'route' => 'supply.ai-extractions.index', 'active' => 'supply.ai-extractions.*'],
            ['key' => 'form-templates', 'label' => 'Form Templates', 'route' => 'supply.forms.templates.index', 'active' => 'supply.forms.templates.*'],
            ['key' => 'form-autofill-runs', 'label' => 'Form Autofill Runs', 'route' => 'supply.form-autofill-runs.index', 'active' => 'supply.form-autofill-runs.*'],
            ['key' => 'supplier-confirmations', 'label' => 'Supplier Confirmations', 'route' => 'supply.supplier-confirmations.index', 'active' => 'supply.supplier-confirmations.*'],
            ['key' => 'transport-quotes', 'label' => 'Transport Quotes', 'route' => 'supply.transport.quotes.index', 'active' => 'supply.transport.*'],
            ['key' => 'logistics', 'label' => 'Logistics', 'route' => 'supply.logistics.index', 'active' => 'supply.logistics.*'],
            ['key' => 'imports', 'label' => 'Imports', 'route' => 'supply.imports.index', 'active' => 'supply.imports.*'],
            ['key' => 'exports', 'label' => 'Exports', 'route' => 'supply.exports.index', 'active' => 'supply.exports.*'],
            ['key' => 'audit-logs', 'label' => 'Audit Logs', 'route' => 'supply.audit-logs.index', 'active' => 'supply.audit-logs.*'],
            ['key' => 'settings', 'label' => 'Settings', 'route' => 'supply.settings.index', 'active' => 'supply.settings.*'],
            ['key' => 'integrations', 'label' => 'Integrations', 'route' => 'supply.integrations.index', 'active' => 'supply.integrations.*'],
            ['key' => 'pilots', 'label' => 'Pilot UAT', 'route' => 'supply.pilots.index', 'active' => 'supply.pilots.*'],
        ];
    }

    /**
     * @return array{key:string,label:string,route:string,active:string,fragment?:string,children?:list<array{label:string,route:string,fragment:string}>}|null
     */
    public static function section(string $key): ?array
    {
        foreach (self::items() as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }
}
