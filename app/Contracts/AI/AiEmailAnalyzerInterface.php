<?php

namespace App\Contracts\AI;

interface AiEmailAnalyzerInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function analyze(array $input): array;
}
