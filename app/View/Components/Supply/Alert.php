<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $classes;

    public function __construct(string $tone = 'info')
    {
        $this->classes = trim(implode(' ', array_filter([
            'alert',
            $this->toneClass($tone),
        ])));
    }

    public function render(): View
    {
        return view('components.supply.alert');
    }

    private function toneClass(string $tone): ?string
    {
        return match ($tone) {
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'error', 'danger' => 'alert-error',
            default => 'alert-info',
        };
    }
}
