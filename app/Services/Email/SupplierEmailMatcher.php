<?php

namespace App\Services\Email;

use App\Models\Company;
use App\Models\SupplierContact;
use Illuminate\Support\Str;

class SupplierEmailMatcher
{
    /**
     * @return array{supplier_id:?int,confidence:float,method:string,warnings:list<string>}
     */
    public function match(Company $company, ?string $fromEmail): array
    {
        $email = Str::lower(trim((string) $fromEmail));

        if ($email === '' || ! str_contains($email, '@')) {
            return $this->noMatch(['unknown_supplier']);
        }

        $contact = SupplierContact::query()
            ->select(['id', 'supplier_id', 'email'])
            ->where('email', $email)
            ->whereHas('supplier', fn ($query) => $query->where('company_id', $company->id))
            ->first();

        if ($contact instanceof SupplierContact) {
            return [
                'supplier_id' => (int) $contact->supplier_id,
                'confidence' => 0.95,
                'method' => 'exact_contact_email',
                'warnings' => [],
            ];
        }

        $domain = Str::after($email, '@');

        if ($domain === '') {
            return $this->noMatch(['unknown_supplier']);
        }

        $supplierIds = SupplierContact::query()
            ->select(['id', 'supplier_id', 'email'])
            ->whereHas('supplier', fn ($query) => $query->where('company_id', $company->id))
            ->get()
            ->filter(fn (SupplierContact $supplierContact): bool => Str::lower(Str::after((string) $supplierContact->email, '@')) === $domain)
            ->pluck('supplier_id')
            ->unique()
            ->values();

        if ($supplierIds->count() === 1) {
            return [
                'supplier_id' => (int) $supplierIds->first(),
                'confidence' => 0.65,
                'method' => 'unique_contact_domain',
                'warnings' => ['supplier_matched_by_domain'],
            ];
        }

        if ($supplierIds->count() > 1) {
            return $this->noMatch(['supplier_domain_ambiguous']);
        }

        return $this->noMatch(['unknown_supplier']);
    }

    /**
     * @param  list<string>  $warnings
     * @return array{supplier_id:null,confidence:float,method:string,warnings:list<string>}
     */
    private function noMatch(array $warnings): array
    {
        return [
            'supplier_id' => null,
            'confidence' => 0.0,
            'method' => 'none',
            'warnings' => $warnings,
        ];
    }
}
