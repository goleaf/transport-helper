<?php

namespace App\View\Components\Supply;

use App\Models\FormAutofillRun;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormAutofillApplications extends Component
{
    public string $contextType;

    public string $runStatus;

    public bool $isValidated;

    public bool $canShowSupplierConfirmationForm;

    public bool $canShowCarrierQuoteForm;

    public function __construct(
        public FormAutofillRun $run,
        public bool $canApplySupplierConfirmation = false,
        public bool $canApplyCarrierQuote = false
    ) {
        $this->contextType = DisplayValue::scalar($run->formTemplate?->context_type);
        $this->runStatus = DisplayValue::scalar($run->status);
        $this->isValidated = $this->runStatus === 'validated';
        $compatibleSupplierConfirmation = in_array($this->contextType, ['supplier_confirmation', 'ready_date_update', 'quantity_mismatch'], true);
        $this->canShowSupplierConfirmationForm = $this->isValidated && $compatibleSupplierConfirmation && $canApplySupplierConfirmation;
        $this->canShowCarrierQuoteForm = $this->isValidated && $this->contextType === 'carrier_quote' && $canApplyCarrierQuote;
    }

    public function render(): View
    {
        return view('components.supply.form-autofill-applications');
    }
}
