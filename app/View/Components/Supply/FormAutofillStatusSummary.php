<?php

namespace App\View\Components\Supply;

use App\Models\FormAutofillRun;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormAutofillStatusSummary extends Component
{
    public string $contextLabel;

    public int $fieldsRequiringReviewCount;

    public function __construct(public FormAutofillRun $run)
    {
        $this->contextLabel = DisplayValue::humanLabel($run->formTemplate?->context_type);
        $this->fieldsRequiringReviewCount = $run->fields_requiring_review_count;
    }

    public function render(): View
    {
        return view('components.supply.form-autofill-status-summary');
    }
}
