<?php

namespace App\Actions;

use Illuminate\Support\Carbon;

class ExtractManufacturerEmailFieldsAction
{
    /**
     * @return array{
     *     order_number: string|null,
     *     confirmation_number: string|null,
     *     ready_on: string|null,
     *     pickup_on: string|null
     * }
     */
    public function handle(string $subject, string $body): array
    {
        $text = $subject."\n".$body;

        return [
            'order_number' => $this->match('/\bSO-\d{8}-[A-Z0-9]{6}\b/i', $text),
            'confirmation_number' => $this->match('/\bCONF-[A-Z0-9-]+\b/i', $text),
            'ready_on' => $this->dateAfterLabel(['ready date', 'ready', 'готово'], $text),
            'pickup_on' => $this->dateAfterLabel(['pickup', 'pickup date', 'забор'], $text),
        ];
    }

    private function match(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        return mb_strtoupper($matches[0]);
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function dateAfterLabel(array $labels, string $text): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/'.preg_quote($label, '/').'\s*[:#-]?\s*(\d{4}-\d{2}-\d{2})/iu';

            if (preg_match($pattern, $text, $matches) === 1) {
                return Carbon::parse($matches[1])->toDateString();
            }
        }

        return null;
    }
}
