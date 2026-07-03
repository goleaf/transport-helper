<?php

namespace App\Services\Supply\Forecasting;

use App\Enums\TrendOverrideStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TrendOverride;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class TrendOverrideService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{override: TrendOverride}
     */
    public function createOverride(array $validated, User $user): array
    {
        $this->validateOverride($validated);

        $override = TrendOverride::query()->create($validated + [
            'created_by_user_id' => $user->getKey(),
            'status' => TrendOverrideStatus::Draft,
        ]);

        $this->auditLogService->write('trend_override_created', $override, $user, null, [
            'trend_value' => $override->trend_value,
            'reason' => $override->reason,
        ], [], $override->company_id);

        return ['override' => $override];
    }

    /**
     * @return array{override: TrendOverride}
     */
    public function submitForApproval(TrendOverride $override, User $user): array
    {
        $oldStatus = $override->status;
        $override->forceFill(['status' => TrendOverrideStatus::PendingApproval])->save();

        $this->auditLogService->write('trend_override_submitted_for_approval', $override, $user, [
            'status' => $this->scalar($oldStatus),
        ], [
            'status' => $this->scalar($override->status),
        ], [], $override->company_id);

        return ['override' => $override->refresh()];
    }

    /**
     * @return array{override: TrendOverride}
     */
    public function approve(TrendOverride $override, User $user, string $note): array
    {
        if (trim($note) === '') {
            throw new InvalidArgumentException('Approval note is required.');
        }

        $old = $override->getOriginal();
        $override->forceFill([
            'status' => TrendOverrideStatus::Approved,
            'approval_note' => $note,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $this->auditLogService->write('trend_override_approved', $override, $user, $old, [
            'status' => $this->scalar($override->status),
            'approval_note' => $note,
        ], [], $override->company_id);

        return ['override' => $override->refresh()];
    }

    /**
     * @return array{override: TrendOverride}
     */
    public function reject(TrendOverride $override, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Rejection reason is required.');
        }

        $old = $override->getOriginal();
        $override->forceFill([
            'status' => TrendOverrideStatus::Rejected,
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogService->write('trend_override_rejected', $override, $user, $old, [
            'status' => $this->scalar($override->status),
            'rejection_reason' => $reason,
        ], [], $override->company_id);

        return ['override' => $override->refresh()];
    }

    /**
     * @return array{override: TrendOverride}
     */
    public function revoke(TrendOverride $override, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Revocation reason is required.');
        }

        $old = $override->getOriginal();
        $override->forceFill([
            'status' => TrendOverrideStatus::Revoked,
            'rejection_reason' => $reason,
            'revoked_by_user_id' => $user->getKey(),
            'revoked_at' => now(),
        ])->save();

        $this->auditLogService->write('trend_override_revoked', $override, $user, $old, [
            'status' => $this->scalar($override->status),
            'revocation_reason' => $reason,
        ], [], $override->company_id);

        return ['override' => $override->refresh()];
    }

    /**
     * @return array{override: TrendOverride|null, usable: bool, warnings: list<string>, explanation: array<string, mixed>}
     */
    public function findApplicable(Company $company, Product $product, ?Supplier $supplier = null, ?string $date = null): array
    {
        $date ??= now()->toDateString();
        $supplierId = $supplier?->getKey();

        $override = TrendOverride::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'trend_value', 'date_from', 'date_to', 'status', 'reason', 'approval_note', 'created_by_user_id', 'approved_by_user_id', 'approved_at'])
            ->approvedActive($company, $date)
            ->where(function ($query) use ($product): void {
                $query->whereNull('product_id')->orWhere('product_id', $product->getKey());
            })
            ->where(function ($query) use ($product): void {
                $query->whereNull('category')->orWhere('category', $product->category);
            })
            ->where(function ($query) use ($supplierId): void {
                $query->whereNull('supplier_id');

                if ($supplierId !== null) {
                    $query->orWhere('supplier_id', $supplierId);
                }
            })
            ->limit(100)
            ->get()
            ->sortByDesc(fn (TrendOverride $candidate): int => $this->specificity($candidate, $product, $supplier))
            ->first();

        return [
            'override' => $override,
            'usable' => $override instanceof TrendOverride,
            'warnings' => [],
            'explanation' => $override instanceof TrendOverride ? [
                'id' => $override->getKey(),
                'trend_value' => (float) $override->trend_value,
                'reason' => $override->reason,
                'approved_at' => $override->approved_at?->toISOString(),
            ] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateOverride(array $data): void
    {
        if ((float) ($data['trend_value'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Trend override value must be greater than zero.');
        }

        if (trim((string) ($data['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('Trend override requires a reason.');
        }

        if (($data['date_from'] ?? null) > ($data['date_to'] ?? null)) {
            throw new InvalidArgumentException('Trend override date_from must be before date_to.');
        }
    }

    private function specificity(TrendOverride $override, Product $product, ?Supplier $supplier): int
    {
        if ($override->product_id !== null && $override->supplier_id !== null && (int) $override->product_id === (int) $product->getKey() && (int) $override->supplier_id === (int) $supplier?->getKey()) {
            return 100;
        }

        if ($override->product_id !== null && (int) $override->product_id === (int) $product->getKey()) {
            return 90;
        }

        if ($override->supplier_id !== null && $override->category !== null) {
            return 80;
        }

        if ($override->category !== null) {
            return 60;
        }

        if ($override->supplier_id !== null) {
            return 40;
        }

        return 10;
    }

    private function scalar(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
