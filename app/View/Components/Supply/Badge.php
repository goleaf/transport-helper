<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Badge extends Component
{
    public string $classes;

    public function __construct(
        string $variant = 'outline',
        string $size = 'md',
        ?string $tone = null,
    ) {
        $this->classes = trim(implode(' ', array_filter([
            'badge',
            $this->variantClass($variant),
            $this->sizeClass($size),
            $tone ? $this->toneClass($tone) : null,
        ])));
    }

    public function render(): View
    {
        return view('components.supply.badge');
    }

    private function variantClass(string $variant): ?string
    {
        return match ($variant) {
            'soft' => 'badge-soft',
            'solid', 'filled' => null,
            default => 'badge-outline',
        };
    }

    private function sizeClass(string $size): string
    {
        return match ($size) {
            'sm', 'small' => 'badge-sm',
            'lg', 'large' => 'badge-lg',
            default => 'badge-md',
        };
    }

    private function toneClass(string $tone): ?string
    {
        return match ($tone) {
            'primary' => 'badge-primary',
            'secondary' => 'badge-secondary',
            'accent' => 'badge-accent',
            'neutral' => 'badge-neutral',
            'info' => 'badge-info',
            'success' => 'badge-success',
            'warning' => 'badge-warning',
            'error', 'danger' => 'badge-error',
            'ai' => 'badge-secondary',
            'logistics' => 'badge-accent',
            'transport' => 'badge-info',
            default => null,
        };
    }
}
