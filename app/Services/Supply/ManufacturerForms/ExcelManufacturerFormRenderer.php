<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Exceptions\NotConfiguredYetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelManufacturerFormRenderer
{
    /**
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    public function render(array $preview, string $templatePath, string $outputPath): array
    {
        if (! class_exists(Spreadsheet::class)) {
            throw NotConfiguredYetException::forAdapter('excel_manufacturer_form_renderer');
        }

        throw NotConfiguredYetException::forAdapter('excel_manufacturer_form_renderer');
    }
}
