<?php

namespace App\View\Components\Supply;

use App\Models\SupplierOrder;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SupplierOrderTransportSummary extends Component
{
    public mixed $selectedLogisticsRecord;

    public mixed $selectedQuote;

    public int $quoteCount;

    public function __construct(
        public SupplierOrder $order,
        public bool $canManageTransport = false
    ) {
        $this->selectedLogisticsRecord = $order->logisticsRecords->first(
            fn ($record): bool => $record->selected_carrier_quote_id !== null,
        ) ?? $order->logisticsRecords->first();

        $this->selectedQuote = $this->selectedLogisticsRecord?->selectedCarrierQuote
            ?? $order->carrierQuotes->first(
                fn ($quote): bool => DisplayValue::statusValue($quote->status) === 'selected',
            );

        $this->quoteCount = $order->carrierQuotes->count();
    }

    public function render(): View
    {
        return view('components.supply.supplier-order-transport-summary');
    }
}
