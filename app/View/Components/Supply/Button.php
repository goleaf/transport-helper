<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public string $classes;

    public function __construct(
        public ?string $href = null,
        public string $type = 'button',
        string $variant = 'primary',
        string $size = 'md',
        string $mode = 'solid',
        public bool $disabled = false,
        bool $wide = false,
        bool $block = false,
    ) {
        $this->classes = trim(implode(' ', array_filter([
            'btn',
            $this->sizeClass($size),
            $this->modeClass($mode),
            $this->variantClass($variant),
            $wide ? 'btn-wide' : null,
            $block ? 'btn-block' : null,
        ])));
    }

    public function render(): View
    {
        return view('components.supply.button');
    }

    private function variantClass(string $variant): ?string
    {
        return match ($variant) {
            'base', 'default' => null,
            'secondary' => 'btn-secondary',
            'accent' => 'btn-accent',
            'neutral' => 'btn-neutral',
            'info' => 'btn-info',
            'success' => 'btn-success',
            'warning' => 'btn-warning',
            'error', 'danger' => 'btn-error',
            default => 'btn-primary',
        };
    }

    private function sizeClass(string $size): string
    {
        return match ($size) {
            'sm', 'small' => 'btn-sm',
            'lg', 'large' => 'btn-lg',
            default => 'btn-md',
        };
    }

    private function modeClass(string $mode): ?string
    {
        return match ($mode) {
            'outline' => 'btn-outline',
            'soft' => 'btn-soft',
            'ghost' => 'btn-ghost',
            'link' => 'btn-link',
            default => null,
        };
    }
}
