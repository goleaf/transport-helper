<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProposalWarnings extends Component
{
    public array $warnings;

    public int $count;

    public string $label;

    public mixed $firstWarning;

    public bool $hasWarnings;

    public function __construct(mixed $warnings = [])
    {
        $this->warnings = is_array($warnings) ? $warnings : [];
        $this->count = count($this->warnings);
        $this->label = $this->count.' warning'.($this->count === 1 ? '' : 's');
        $this->hasWarnings = $this->count > 0;
        $this->firstWarning = $this->warnings[0] ?? [];
    }

    public function render(): View
    {
        return view('components.supply.proposal-warnings');
    }
}
