@extends('layouts.app')

@section('title')
Create Trend Override
@endsection

@section('content')
<x-supply.page-header title="Create Trend Override" subtitle="Manual trend values require reason and approval before use." :back-url="route('supply.forecasting.overrides.index')" />

<section>
    <form method="POST" action="{{ route('supply.forecasting.overrides.store') }}" class="form-grid">
        @csrf

        <label>
            <span>Company</span>
            <select class="select select-bordered" name="company_id" required>
                @forelse ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
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
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
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
                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
                @empty
                    <option value="">No products</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Category</span>
            <input class="input input-bordered" name="category" value="{{ old('category') }}">
        </label>

        <label>
            <span>Trend value</span>
            <input class="input input-bordered" type="number" step="0.000001" min="0.0001" name="trend_value" value="{{ old('trend_value') }}" required>
        </label>

        <label>
            <span>Date from</span>
            <input class="input input-bordered" type="date" name="date_from" value="{{ old('date_from', now()->toDateString()) }}" required>
        </label>

        <label>
            <span>Date to</span>
            <input class="input input-bordered" type="date" name="date_to" value="{{ old('date_to', now()->addMonth()->toDateString()) }}" required>
        </label>

        <label>
            <span>Reason</span>
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
            @error('reason')<span>{{ $message }}</span>@enderror
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Create override</x-supply.button>
        </div>
    </form>
</section>
@endsection
