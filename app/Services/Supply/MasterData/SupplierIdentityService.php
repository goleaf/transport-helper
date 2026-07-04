<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataAliasStatus;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\SupplierContact;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SupplierIdentityService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{alias: SupplierAlias}
     */
    public function createAlias(array $validated, User $user): array
    {
        if (trim((string) ($validated['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('Supplier alias reason is required.');
        }

        $alias = SupplierAlias::query()->create(array_merge($validated, [
            'status' => $this->userCanApprove($user) ? MasterDataAliasStatus::Active : MasterDataAliasStatus::Pending,
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $this->userCanApprove($user) ? $user->getKey() : null,
            'approved_at' => $this->userCanApprove($user) ? now() : null,
        ]));

        $this->auditLogService->write('supplier_alias_created', $alias, $user, null, [
            'supplier_id' => $alias->supplier_id,
            'alias' => $alias->alias,
            'status' => $alias->status?->value,
        ], [], $alias->company_id);

        return ['alias' => $alias];
    }

    /**
     * @return array{alias: SupplierAlias}
     */
    public function approveAlias(SupplierAlias $alias, User $user, string $note): array
    {
        $this->requireReason($note, 'Supplier alias approval note is required.');
        $old = $alias->getOriginal();

        $alias->forceFill([
            'status' => MasterDataAliasStatus::Active,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'reason' => $alias->reason ?: $note,
        ])->save();

        $this->auditLogService->write('supplier_alias_approved', $alias, $user, $old, $alias->getChanges(), [
            'note' => $note,
        ], $alias->company_id);

        return ['alias' => $alias->refresh()];
    }

    /**
     * @return array{alias: SupplierAlias}
     */
    public function rejectAlias(SupplierAlias $alias, User $user, string $reason): array
    {
        $this->requireReason($reason, 'Supplier alias rejection reason is required.');
        $old = $alias->getOriginal();

        $alias->forceFill([
            'status' => MasterDataAliasStatus::Rejected,
            'reason' => $reason,
        ])->save();

        $this->auditLogService->write('supplier_alias_rejected', $alias, $user, $old, $alias->getChanges(), [], $alias->company_id);

        return ['alias' => $alias->refresh()];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{matched: bool, supplier: Supplier|null, matched_by: string|null, confidence: float, requires_review: bool, warnings: list<string>, suggestions: list<array<string,mixed>>}
     */
    public function resolve(Company $company, array $input): array
    {
        $warnings = [];
        $supplier = null;
        $matchedBy = null;

        if (! empty($input['supplier_id'])) {
            $supplier = Supplier::query()
                ->select(['id', 'company_id', 'name', 'code', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->whereKey($input['supplier_id'])
                ->first();
            $matchedBy = $supplier instanceof Supplier ? 'supplier_id' : null;
        }

        if (! $supplier instanceof Supplier && trim((string) ($input['code'] ?? '')) !== '') {
            $supplier = Supplier::query()
                ->select(['id', 'company_id', 'name', 'code', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->where('code', trim((string) $input['code']))
                ->first();
            $matchedBy = $supplier instanceof Supplier ? 'supplier_code' : null;
        }

        if (! $supplier instanceof Supplier && trim((string) ($input['from_email'] ?? '')) !== '') {
            $contact = SupplierContact::query()
                ->select(['id', 'supplier_id', 'email'])
                ->with(['supplier:id,company_id,name,code,is_active,lifecycle_status'])
                ->where('email', Str::of((string) $input['from_email'])->lower()->trim()->toString())
                ->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))
                ->first();
            $supplier = $contact?->supplier;
            $matchedBy = $supplier instanceof Supplier ? 'contact_email' : null;
        }

        if (! $supplier instanceof Supplier && trim((string) ($input['alias'] ?? '')) !== '') {
            $alias = SupplierAlias::query()
                ->select(['id', 'company_id', 'supplier_id', 'alias', 'status'])
                ->with(['supplier:id,company_id,name,code,is_active,lifecycle_status'])
                ->active()
                ->whereBelongsTo($company)
                ->where('alias', trim((string) $input['alias']))
                ->first();
            $supplier = $alias?->supplier;
            $matchedBy = $supplier instanceof Supplier ? 'supplier_alias' : null;
        }

        if (! $supplier instanceof Supplier && trim((string) ($input['name'] ?? '')) !== '') {
            $normalizedName = $this->normalizeSupplierName($input['name']);
            $supplier = Supplier::query()
                ->select(['id', 'company_id', 'name', 'code', 'is_active', 'lifecycle_status'])
                ->whereBelongsTo($company)
                ->get()
                ->first(fn (Supplier $candidate): bool => $this->normalizeSupplierName($candidate->name) === $normalizedName);
            $matchedBy = $supplier instanceof Supplier ? 'supplier_name_normalized' : null;
        }

        if (! $supplier instanceof Supplier && ($domain = $this->domainFromInput($input)) !== null) {
            $contacts = SupplierContact::query()
                ->select(['id', 'supplier_id', 'email'])
                ->with(['supplier:id,company_id,name,code,is_active,lifecycle_status'])
                ->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))
                ->get()
                ->filter(fn (SupplierContact $contact): bool => $this->emailDomain($contact->email) === $domain)
                ->values();

            if ($contacts->pluck('supplier_id')->unique()->count() === 1) {
                $supplier = $contacts->first()?->supplier;
                $matchedBy = $supplier instanceof Supplier ? 'unique_email_domain' : null;
                $warnings[] = 'matched_by_unique_email_domain';
            } elseif ($contacts->isNotEmpty()) {
                $warnings[] = 'ambiguous_supplier_email_domain';
            }
        }

        if ($supplier instanceof Supplier) {
            $warnings = array_merge($warnings, $this->supplierWarnings($supplier));
        }

        return [
            'matched' => $supplier instanceof Supplier,
            'supplier' => $supplier,
            'matched_by' => $matchedBy,
            'confidence' => $supplier instanceof Supplier ? 1.0 : 0.0,
            'requires_review' => ! $supplier instanceof Supplier || $warnings !== [],
            'warnings' => $warnings,
            'suggestions' => $supplier instanceof Supplier ? [] : $this->suggestions($company, $input),
        ];
    }

    public function normalizeSupplierName(mixed $name): ?string
    {
        $value = Str::of((string) $name)->lower()->replaceMatches('/[^a-z0-9]+/i', ' ')->squish()->toString();

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{id:int,name:string,score:float,matched_by:string}>
     */
    public function suggestions(Company $company, array $input): array
    {
        $name = $this->normalizeSupplierName($input['name'] ?? '');

        if ($name === null) {
            return [];
        }

        $suggestions = [];
        Supplier::query()
            ->select(['id', 'company_id', 'name'])
            ->whereBelongsTo($company)
            ->limit(200)
            ->get()
            ->each(function (Supplier $supplier) use ($name, &$suggestions): void {
                similar_text($name, (string) $this->normalizeSupplierName($supplier->name), $percent);

                if ($percent >= 70.0) {
                    $suggestions[] = [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'score' => round($percent / 100, 4),
                        'matched_by' => 'similar_name_suggestion',
                    ];
                }
            });

        return collect($suggestions)->sortByDesc('score')->take(10)->values()->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function domainFromInput(array $input): ?string
    {
        if (! empty($input['domain'])) {
            return Str::of((string) $input['domain'])->lower()->trim()->toString();
        }

        return $this->emailDomain((string) ($input['from_email'] ?? ''));
    }

    private function emailDomain(?string $email): ?string
    {
        $email = Str::of((string) $email)->lower()->trim()->toString();

        if (! str_contains($email, '@')) {
            return null;
        }

        return Str::of($email)->after('@')->toString();
    }

    /**
     * @return list<string>
     */
    private function supplierWarnings(Supplier $supplier): array
    {
        $warnings = [];
        $status = (string) $supplier->lifecycle_status;

        if (! $supplier->is_active) {
            $warnings[] = 'supplier_inactive';
        }

        if (in_array($status, ['merged', 'archived', 'blocked', 'inactive'], true)) {
            $warnings[] = 'supplier_lifecycle_'.$status;
        }

        return $warnings;
    }

    private function userCanApprove(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermissionTo('manage_products');
    }

    private function requireReason(mixed $reason, string $message): void
    {
        if (trim((string) $reason) === '') {
            throw new InvalidArgumentException($message);
        }
    }
}
