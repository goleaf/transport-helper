@extends('layouts.app')

@section('title')
Sales Exclusion Rule
@endsection

@section('content')
<x-supply.page-header title="Sales Exclusion Rule" subtitle="Promotions, anomalies and approved manual exclusions are never deleted from history." :back-url="route('supply.forecasting.exclusions.index')" />

<section>
    <form method="POST" action="{{ isset($rule) ? route('supply.forecasting.exclusions.update', $rule) : route('supply.forecasting.exclusions.store') }}" class="form-grid">
        @csrf
        @isset($rule)
            @method('PATCH')
        @endisset

        <label>
            <span>Company</span>
            <select class="select select-bordered" name="company_id" required>
                @forelse ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $rule?->company_id ?? null) === (string) $company->id)>{{ $company->name }}</option>
                @empty
                    <option value="">No companies</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Supplier</span>
            <select class="select select-bordered" name="supplier_id">
                <option value="">Any supplier</option>
                @forelse ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $rule?->supplier_id ?? null) === (string) $supplier->id)>{{ $supplier->name }}</option>
                @empty
                    <option value="">No suppliers</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Product</span>
            <select class="select select-bordered" name="product_id">
                <option value="">Any product</option>
                @forelse ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id', $rule?->product_id ?? null) === (string) $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
                @empty
                    <option value="">No products</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Category</span>
            <input class="input input-bordered" name="category" value="{{ old('category', $rule?->category ?? null) }}">
        </label>

        <label>
            <span>Rule type</span>
            <select class="select select-bordered" name="rule_type" required>
                @forelse ($ruleTypes as $type)
                    <option value="{{ $type }}" @selected(old('rule_type', isset($rule) ? $rule->rule_type->value : 'manual_exclusion') === $type)>{{ $type }}</option>
                @empty
                    <option value="manual_exclusion">manual_exclusion</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Applies to</span>
            <select class="select select-bordered" name="applies_to" required>
                @forelse ($appliesTo as $period)
                    <option value="{{ $period }}" @selected(old('applies_to', $rule?->applies_to ?? 'all_calculation_periods') === $period)>{{ $period }}</option>
                @empty
                    <option value="all_calculation_periods">all_calculation_periods</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Date from</span>
            <input class="input input-bordered" type="date" name="date_from" value="{{ old('date_from', isset($rule) ? $rule->date_from?->toDateString() : now()->toDateString()) }}" required>
        </label>

        <label>
            <span>Date to</span>
            <input class="input input-bordered" type="date" name="date_to" value="{{ old('date_to', isset($rule) ? $rule->date_to?->toDateString() : now()->toDateString()) }}" required>
        </label>

        <label>
            <span>Reason</span>
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason', $rule?->reason ?? null) }}</textarea>
            @error('reason')<span>{{ $message }}</span>@enderror
        </label>

        <label>
            <input type="hidden" name="is_active" value="0">
            <input class="checkbox" type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule?->is_active ?? true))>
            <span>Active</span>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">{{ isset($rule) ? 'Save exclusion' : 'Create exclusion' }}</x-supply.button>
        </div>
    </form>
</section>
@endsection
