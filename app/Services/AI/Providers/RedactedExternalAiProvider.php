<?php

namespace App\Services\AI\Providers;

use App\Enums\IntegrationApprovalStatus;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\AI\Redaction\AiInputRedactionService;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class RedactedExternalAiProvider
{
    public function __construct(
        private readonly AiInputRedactionService $redactionService,
        private readonly ExternalAiProviderPlaceholder $provider,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function suggest(array $input, array $rules = [], ?User $user = null): array
    {
        if (! (bool) config('supply.external_ai.enabled', false)) {
            throw ValidationException::withMessages([
                'external_ai' => 'External AI is disabled by default.',
            ]);
        }

        $connection = IntegrationConnection::query()
            ->where('provider', 'external_ai')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $connection || $connection->approval_status !== IntegrationApprovalStatus::Approved->value) {
            throw ValidationException::withMessages([
                'approval_status' => 'External AI requires an approved active integration.',
            ]);
        }

        $redacted = $this->redactionService->redact($input, $rules);
        $this->auditLogService->write('external_ai_redaction_performed', $connection, $user, null, null, [
            'redaction_count' => count($redacted['redactions']),
            'redactions' => $redacted['redactions'],
        ], $connection->company_id);

        return $this->provider->suggest($redacted['redacted_input']) + [
            'redactions' => $redacted['redactions'],
        ];
    }
}
