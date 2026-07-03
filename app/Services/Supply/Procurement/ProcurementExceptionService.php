<?php

namespace App\Services\Supply\Procurement;

use App\Enums\ProcurementApprovalRequestStatus;
use App\Models\Company;
use App\Models\ProcurementException;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementExceptionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{exception: ProcurementException}
     */
    public function requestException(Model $exceptable, string $type, string $reason, User $user, array $metadata = []): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Procurement exception reason is required.']);
        }

        $exception = ProcurementException::query()->create([
            'company_id' => $this->companyId($exceptable),
            'exception_type' => $type,
            'exceptable_type' => $exceptable::class,
            'exceptable_id' => $exceptable->getKey(),
            'status' => ProcurementApprovalRequestStatus::Pending,
            'reason' => $reason,
            'requested_by_user_id' => $user->getKey(),
            'metadata_json' => $metadata,
        ]);

        $this->auditLogService->write('procurement_exception_requested', $exception, $user, null, [
            'exception_type' => $type,
            'exceptable_type' => $exceptable::class,
            'exceptable_id' => $exceptable->getKey(),
        ], [], $exception->company_id);

        return ['exception' => $exception];
    }

    /**
     * @return array{exception: ProcurementException}
     */
    public function approve(ProcurementException $exception, User $user, string $note): array
    {
        return DB::transaction(function () use ($exception, $user, $note): array {
            if ($exception->status !== ProcurementApprovalRequestStatus::Pending) {
                throw ValidationException::withMessages(['exception' => 'Only pending procurement exceptions can be approved.']);
            }

            $oldStatus = $exception->status?->value ?? (string) $exception->status;
            $exception->forceFill([
                'status' => ProcurementApprovalRequestStatus::Approved,
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => now(),
                'metadata_json' => array_merge($exception->metadata_json ?? [], ['approval_note' => $note]),
            ])->save();

            $this->auditLogService->write('procurement_exception_approved', $exception, $user, ['status' => $oldStatus], [
                'status' => ProcurementApprovalRequestStatus::Approved->value,
                'note' => $note,
            ], [], $exception->company_id);

            return ['exception' => $exception->refresh()];
        });
    }

    /**
     * @return array{exception: ProcurementException}
     */
    public function reject(ProcurementException $exception, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Exception rejection reason is required.']);
        }

        if ($exception->status !== ProcurementApprovalRequestStatus::Pending) {
            throw ValidationException::withMessages(['exception' => 'Only pending procurement exceptions can be rejected.']);
        }

        $oldStatus = $exception->status?->value ?? (string) $exception->status;
        $exception->forceFill([
            'status' => ProcurementApprovalRequestStatus::Rejected,
            'rejected_by_user_id' => $user->getKey(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogService->write('procurement_exception_rejected', $exception, $user, ['status' => $oldStatus], [
            'status' => ProcurementApprovalRequestStatus::Rejected->value,
            'reason' => $reason,
        ], [], $exception->company_id);

        return ['exception' => $exception->refresh()];
    }

    /**
     * @param  list<string>  $types
     */
    public function hasApprovedException(Model $exceptable, array $types): bool
    {
        return ProcurementException::query()
            ->where('exceptable_type', $exceptable::class)
            ->where('exceptable_id', $exceptable->getKey())
            ->where('status', ProcurementApprovalRequestStatus::Approved->value)
            ->whereIn('exception_type', $types)
            ->exists();
    }

    private function companyId(Model $model): int
    {
        $companyId = $model->getAttribute('company_id');

        return is_numeric($companyId) ? (int) $companyId : (int) Company::query()->select(['id'])->value('id');
    }
}
