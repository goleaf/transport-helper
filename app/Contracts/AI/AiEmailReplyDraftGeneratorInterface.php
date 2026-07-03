<?php

namespace App\Contracts\AI;

interface AiEmailReplyDraftGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function generate(array $input): array;
}
