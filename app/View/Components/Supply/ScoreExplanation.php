<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScoreExplanation extends Component
{
    public string $summary;

    public array $subscores;

    public array $penalties;

    public bool $hasSubscores;

    public bool $hasPenalties;

    public function __construct(mixed $explanation = [])
    {
        $explanation = is_array($explanation) ? $explanation : [];
        $this->summary = (string) ($explanation['summary'] ?? 'Not scored yet.');
        $this->subscores = collect($explanation['subscores'] ?? [])->map(fn (mixed $value, string|int $key): array => [
            'label' => DisplayValue::title((string) $key),
            'value' => $value,
        ])->values()->all();
        $this->penalties = collect($explanation['penalties'] ?? [])->map(fn (mixed $penalty): array => [
            'type' => is_array($penalty) ? ($penalty['type'] ?? 'penalty') : 'penalty',
            'points' => is_array($penalty) ? ($penalty['points'] ?? '') : '',
        ])->values()->all();
        $this->hasSubscores = $this->subscores !== [];
        $this->hasPenalties = $this->penalties !== [];
    }

    public function render(): View
    {
        return view('components.supply.score-explanation');
    }
}
