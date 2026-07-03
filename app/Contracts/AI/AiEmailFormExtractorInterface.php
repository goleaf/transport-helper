<?php

namespace App\Contracts\AI;

interface AiEmailFormExtractorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function extract(array $input): array;
}
