<?php

namespace App\Services\Supply\UI;

use App\Models\ReportRun;
use App\Support\DisplayValue;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Collection;

class AnalyticsPresentationService
{
    /**
     * @param  array<string, mixed>  $summary
     * @return list<array{title:string,value:string}>
     */
    public function summaryCards(array $summary): array
    {
        return collect($summary)
            ->map(fn (mixed $value, string|int $key): array => [
                'title' => DisplayValue::headline((string) $key),
                'value' => $this->display($value),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array{headers:list<array{key:string,label:string}>,rows:list<array{cells:list<string>}>,empty_colspan:int}
     */
    public function table(array $rows): array
    {
        $normalizedRows = collect($rows)
            ->map(fn (mixed $row): array => is_array($row) ? $row : ['value' => $row])
            ->values();
        $keys = $normalizedRows
            ->flatMap(fn (array $row): array => array_keys($row))
            ->unique()
            ->values();

        return [
            'headers' => $keys
                ->map(fn (string|int $key): array => [
                    'key' => (string) $key,
                    'label' => DisplayValue::headline((string) $key),
                ])
                ->values()
                ->all(),
            'rows' => $normalizedRows
                ->map(fn (array $row): array => [
                    'cells' => $keys
                        ->map(fn (string|int $key): string => $this->display($row[$key] ?? ''))
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'empty_colspan' => max($keys->count(), 1),
        ];
    }

    /**
     * @param  array<string, mixed>  $definitions
     * @return list<array{name:string,formula:string,limitations:string}>
     */
    public function definitions(array $definitions): array
    {
        return collect($definitions)
            ->map(fn (array $definition): array => [
                'name' => $this->display($definition['name'] ?? 'Unnamed KPI'),
                'formula' => $this->display($definition['formula'] ?? 'No formula'),
                'limitations' => $this->display($definition['limitations'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{headers:list<array{key:string,label:string}>,rows:list<array{cells:list<string>}>,empty_colspan:int}
     */
    public function runSummaryTable(ReportRun $run): array
    {
        $summary = is_array($run->result_summary_json) ? $run->result_summary_json : [];
        $rows = collect($summary)
            ->map(fn (mixed $value, string|int $key): array => [
                'metric' => DisplayValue::headline((string) $key),
                'value' => $this->display($value),
            ])
            ->values()
            ->all();

        return $this->table($rows);
    }

    /**
     * @return list<array{type:string,label:string}>
     */
    public function reportLinks(): array
    {
        return [
            ['type' => 'supplier_performance', 'label' => 'Supplier Performance'],
            ['type' => 'forecast_accuracy', 'label' => 'Forecast Accuracy'],
            ['type' => 'stockout_risk', 'label' => 'Stockout Risk'],
            ['type' => 'order_proposal_quality', 'label' => 'Order Proposal Quality'],
            ['type' => 'supplier_confirmation_mismatches', 'label' => 'Supplier Confirmation Mismatches'],
            ['type' => 'transport_performance', 'label' => 'Transport Performance'],
            ['type' => 'logistics_performance', 'label' => 'Logistics Performance'],
            ['type' => 'receiving_accuracy', 'label' => 'Receiving Accuracy'],
            ['type' => 'data_quality', 'label' => 'Data Quality'],
            ['type' => 'audit_kpis', 'label' => 'Audit KPIs'],
            ['type' => 'operator_efficiency', 'label' => 'Operator Efficiency'],
            ['type' => 'import_quality', 'label' => 'Import Quality'],
            ['type' => 'email_ai_review_quality', 'label' => 'Email AI Review Quality'],
            ['type' => 'form_autofill_quality', 'label' => 'Form Autofill Quality'],
        ];
    }

    private function display(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return DisplayValue::humanLabel($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof Collection) {
            return $this->display($value->all());
        }

        if (is_array($value)) {
            $items = collect($value)
                ->map(function (mixed $item, string|int $key): string {
                    $displayed = $this->display($item);

                    return is_int($key) ? $displayed : DisplayValue::headline((string) $key).': '.$displayed;
                })
                ->filter(fn (string $item): bool => $item !== '')
                ->values()
                ->all();

            return $items === [] ? 'None' : implode('; ', $items);
        }

        return DisplayValue::scalar($value, 'None') ?: 'None';
    }
}
