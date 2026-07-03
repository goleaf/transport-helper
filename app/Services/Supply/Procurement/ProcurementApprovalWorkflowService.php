<?php

namespace App\Services\Supply\Procurement;

use App\Enums\ProcurementApprovalDecisionType;
use App\Enums\ProcurementApprovalRequestStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ProcurementApprovalRequest;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementApprovalWorkflowService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  list<array<string, mixed>>  $requirements
     * @return array{request: ProcurementApprovalRequest}
     */
    public function requestApproval(Model $approvable, array $requirements, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Procurement approval reason is required.']);
        }

        $primary = $requirements[0] ?? [];
        $request = ProcurementApprovalRequest::query()->create([
            'company_id' => $this->companyId($approvable),
            'approvable_type' => $approvable::class,
            'approvable_id' => $approvable->getKey(),
            'status' => ProcurementApprovalRequestStatus::Pending,
            'requested_by_user_id' => $user->getKey(),
            'required_role' => $primary['required_role'] ?? null,
            'required_permission' => $primary['required_permission'] ?? null,
            'amount' => $primary['amount'] ?? null,
            'currency' => $primary['currency'] ?? null,
            'reason' => $reason,
            'metadata_json' => ['requirements' => $requirements],
        ]);

        $this->auditLogService->write('procurement_approval_requested', $request, $user, null, [
            'approvable_type' => $approvable::class,
            'approvable_id' => $approvable->getKey(),
            'requirements' => $requirements,
        ], [], $request->company_id);

        return ['request' => $request];
    }

    /**
     * @return array{request: ProcurementApprovalRequest}
     */
    public function approve(ProcurementApprovalRequest $request, User $user, string $note): array
    {
        return $this->decide($request, $user, ProcurementApprovalDecisionType::Approved, $note);
    }

    /**
     * @return array{request: ProcurementApprovalRequest}
     */
    public function reject(ProcurementApprovalRequest $request, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Rejection reason is required.']);
        }

        return $this->decide($request, $user, ProcurementApprovalDecisionType::Rejected, $reason);
    }

    /**
     * @return array{request: ProcurementApprovalRequest}
     */
    public function cancel(ProcurementApprovalRequest $request, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Cancellation reason is required.']);
        }

        $oldStatus = $request->status?->value ?? (string) $request->status;
        $request->forceFill([
            'status' => ProcurementApprovalRequestStatus::Cancelled,
            'resolved_at' => now(),
            'metadata_json' => array_merge($request->metadata_json ?? [], ['cancel_reason' => $reason]),
        ])->save();

        $this->auditLogService->write('procurement_approval_cancelled', $request, $user, ['status' => $oldStatus], [
            'status' => ProcurementApprovalRequestStatus::Cancelled->value,
            'reason' => $reason,
        ], [], $request->company_id);

        return ['request' => $request->refresh()];
    }

    /**
     * @param  list<array<string, mixed>>  $requirements
     * @return array{sufficient: bool, approved_request_ids: list<int>, warnings: list<string>}
     */
    public function hasSufficientApproval(Model $approvable, array $requirements): array
    {
        if ($requirements === []) {
            return ['sufficient' => true, 'approved_request_ids' => [], 'warnings' => []];
        }

        $approved = ProcurementApprovalRequest::query()
            ->select(['id', 'company_id', 'approvable_type', 'approvable_id', 'status', 'expires_at'])
            ->where('approvable_type', $approvable::class)
            ->where('approvable_id', $approvable->getKey())
            ->where('status', ProcurementApprovalRequestStatus::Approved->value)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderByDesc('id')
            ->get();

        return [
            'sufficient' => $approved->isNotEmpty(),
            'approved_request_ids' => $approved->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
            'warnings' => $approved->isNotEmpty() ? [] : ['procurement_approval_missing'],
        ];
    }

    private function decide(ProcurementApprovalRequest $request, User $user, ProcurementApprovalDecisionType $decision, string $note): array
    {
        if ($request->status !== ProcurementApprovalRequestStatus::Pending) {
            throw ValidationException::withMessages(['request' => 'Only pending procurement approval requests can be decided.']);
        }

        if ((int) $request->requested_by_user_id === (int) $user->getKey() && ! $this->selfApprovalAllowed($user)) {
            throw ValidationException::withMessages(['request' => 'Requester cannot approve their own procurement approval request.']);
        }

        if (! $this->userCanDecide($request, $user)) {
            throw ValidationException::withMessages(['request' => 'User cannot decide this procurement approval request.']);
        }

        return DB::transaction(function () use ($request, $user, $decision, $note): array {
            $oldStatus = $request->status?->value ?? (string) $request->status;
            $newStatus = $decision === ProcurementApprovalDecisionType::Approved
                ? ProcurementApprovalRequestStatus::Approved
                : ProcurementApprovalRequestStatus::Rejected;

            $request->decisions()->create([
                'decision' => $decision,
                'decision_by_user_id' => $user->getKey(),
                'note' => $note,
                'metadata_json' => [],
                'decided_at' => now(),
            ]);

            $request->forceFill([
                'status' => $newStatus,
                'resolved_at' => now(),
            ])->save();

            $event = $decision === ProcurementApprovalDecisionType::Approved
                ? 'procurement_approval_approved'
                : 'procurement_approval_rejected';
            $this->auditLogService->write($event, $request, $user, ['status' => $oldStatus], [
                'status' => $newStatus->value,
                'note' => $note,
            ], [], $request->company_id);

            return ['request' => $request->refresh('decisions')];
        });
    }

    private function userCanDecide(ProcurementApprovalRequest $request, User $user): bool
    {
        if ($user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings')) {
            return true;
        }

        if ($request->required_role !== null && $user->hasRole($request->required_role)) {
            return true;
        }

        if ($request->required_permission !== null && $user->hasPermissionTo($request->required_permission)) {
            return true;
        }

        return $user->hasPermissionTo('approve_order_proposals');
    }

    private function selfApprovalAllowed(User $user): bool
    {
        return (bool) config('supply.procurement.allow_self_approval', false) && $user->hasRole(UserRole::Admin);
    }

    private function companyId(Model $model): int
    {
        $companyId = $model->getAttribute('company_id');
        if (is_numeric($companyId)) {
            return (int) $companyId;
        }

        if ($model->relationLoaded('company') && $model->company instanceof Company) {
            return (int) $model->company->getKey();
        }

        return (int) Company::query()->select(['id'])->value('id');
    }
}
