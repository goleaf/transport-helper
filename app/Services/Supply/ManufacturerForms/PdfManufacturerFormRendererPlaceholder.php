<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Exceptions\NotConfiguredYetException;

class PdfManufacturerFormRendererPlaceholder
{
    /**
     * @return array<string, mixed>
     */
    public function render(mixed ...$arguments): array
    {
        throw NotConfiguredYetException::forAdapter('pdf_manufacturer_form_renderer');
    }
}
