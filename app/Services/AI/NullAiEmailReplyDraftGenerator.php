<?php

namespace App\Services\AI;

use App\Contracts\AI\AiEmailReplyDraftGeneratorInterface;

class NullAiEmailReplyDraftGenerator implements AiEmailReplyDraftGeneratorInterface
{
    public function generate(array $input): array
    {
        return [
            'subject' => 'Draft reply requires review',
            'body_text' => '',
            'confidence' => 0.0,
            'requires_human_review' => true,
        ];
    }
}
