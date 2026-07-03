<?php

namespace App\View\Components\Supply;

use App\Models\AiEmailExtraction;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AiExtractionApplications extends Component
{
    public array $output;

    public bool $isAccepted;

    public bool $canShowSupplierConfirmationForm;

    public bool $canShowCarrierQuoteForm;

    public function __construct(
        public AiEmailExtraction $extraction,
        public bool $canApplySupplierConfirmation = false,
        public bool $canApplyCarrierQuote = false
    ) {
        $this->output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $emailType = $this->output['email_type'] ?? null;
        $hasConfirmationData = in_array($emailType, ['supplier_confirmation', 'date_update', 'quantity_mismatch'], true)
            || ! empty($this->output['confirmed_items']);
        $hasCarrierQuoteData = $emailType === 'transport_quote' || ! empty($this->output['carrier_quote']);
        $this->isAccepted = $extraction->accepted_at !== null && $extraction->rejected_at === null;
        $this->canShowSupplierConfirmationForm = $this->isAccepted && $hasConfirmationData && $canApplySupplierConfirmation;
        $this->canShowCarrierQuoteForm = $this->isAccepted && $hasCarrierQuoteData && $canApplyCarrierQuote;
    }

    public function render(): View
    {
        return view('components.supply.ai-extraction-applications');
    }
}
