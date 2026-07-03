<?php

namespace App\Services\Supply\Calculation;

use Carbon\CarbonImmutable;
use Throwable;

class CalculationPeriodService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateTimeline(array $input): array
    {
        $errors = [];
        $warnings = [];
        $dates = [];

        foreach (['t0_date', 't1_date', 't2_date', 't3_date'] as $key) {
            if (empty($input[$key])) {
                $errors[] = $key.'_missing';

                continue;
            }

            try {
                $dates[$key] = CarbonImmutable::parse($input[$key])->toDateString();
            } catch (Throwable) {
                $errors[] = $key.'_invalid';
            }
        }

        if ($errors !== []) {
            return $this->result(false, $errors, $warnings, $dates);
        }

        $t0 = CarbonImmutable::parse($dates['t0_date']);
        $t1 = CarbonImmutable::parse($dates['t1_date']);
        $t2 = CarbonImmutable::parse($dates['t2_date']);
        $t3 = CarbonImmutable::parse($dates['t3_date']);

        if ($t0->greaterThan($t1)) {
            $errors[] = 't0_after_t1';
        }

        if ($t1->greaterThan($t2)) {
            $errors[] = 't1_after_t2';
        }

        if ($t2->greaterThan($t3)) {
            $errors[] = 't2_after_t3';
        }

        if ($t0->equalTo($t1)) {
            $warnings[] = 't0_t1_zero_length';
        }

        if ($t1->equalTo($t2)) {
            $warnings[] = 't1_t2_zero_length';
        }

        if ($t2->equalTo($t3)) {
            $warnings[] = 't2_t3_zero_length';
        }

        return $this->result($errors === [], $errors, $warnings, $dates);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function describeTimeline(array $input): array
    {
        $validated = $this->validateTimeline($input);

        return [
            'points' => [
                't0_date' => 'today_or_order_date',
                't1_date' => 'expected_goods_arrival_date',
                't2_date' => 'end_of_planned_coverage_period',
                't3_date' => 'end_of_safety_horizon',
            ],
            'dates' => $validated['dates'],
            'periods' => $validated['periods'],
            'note' => 'Safety stock covers only T2-T3 and must not duplicate T1-T2.',
            'warnings' => $validated['warnings'],
            'errors' => $validated['errors'],
        ];
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  array<string, string>  $dates
     * @return array<string, mixed>
     */
    private function result(bool $valid, array $errors, array $warnings, array $dates): array
    {
        return [
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'dates' => $dates,
            'periods' => [
                't0_t1' => [
                    'start' => $dates['t0_date'] ?? null,
                    'end' => $dates['t1_date'] ?? null,
                    'purpose' => 'order_execution_period',
                ],
                't1_t2' => [
                    'start' => $dates['t1_date'] ?? null,
                    'end' => $dates['t2_date'] ?? null,
                    'purpose' => 'planned_coverage_period',
                ],
                't2_t3' => [
                    'start' => $dates['t2_date'] ?? null,
                    'end' => $dates['t3_date'] ?? null,
                    'purpose' => 'safety_horizon',
                ],
            ],
        ];
    }
}
