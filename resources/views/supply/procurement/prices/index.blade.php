@extends('layouts.app')

@section('title')
Supplier Product Prices
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Supplier Product Prices</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Price record</p>
            <h2>Create supplier product price</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.prices.store') }}" class="form-grid">
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
            <select class="select select-bordered" name="supplier_id" required>
                @forelse ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @empty
                    <option value="">No suppliers</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Product</span>
            <select class="select select-bordered" name="product_id" required>
                @forelse ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
                @empty
                    <option value="">No products</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Currency</span>
            <input class="input input-bordered" name="currency" maxlength="3" value="{{ old('currency', 'EUR') }}" required>
        </label>

        <label>
            <span>Unit price</span>
            <input class="input input-bordered" type="number" step="0.0001" min="0" name="unit_price" value="{{ old('unit_price') }}" required>
        </label>

        <label>
            <span>Valid from</span>
            <input class="input input-bordered" type="date" name="valid_from" value="{{ old('valid_from', now()->toDateString()) }}" required>
        </label>

        <label>
            <span>Valid to</span>
            <input class="input input-bordered" type="date" name="valid_to" value="{{ old('valid_to') }}">
        </label>

        <label>
            <span>Source type</span>
            <input class="input input-bordered" name="source_type" value="{{ old('source_type', 'manual') }}">
        </label>

        <label>
            <span>Source reference</span>
            <input class="input input-bordered" name="source_reference" value="{{ old('source_reference') }}">
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Create price</x-supply.button>
        </div>
    </form>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Price history</p>
            <h2>Active and archived records</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Product</th>
                <th>Price</th>
                <th>Valid period</th>
                <th>Status</th>
                <th>Created by</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prices as $price)
                <tr>
                    <td>
                        <strong>{{ $price->supplier?->name }}</strong>
                        <span>{{ $price->company?->name }}</span>
                    </td>
                    <td>
                        <strong>{{ $price->product?->sku }}</strong>
                        <span>{{ $price->product?->name }}</span>
                    </td>
                    <td>{{ number_format((float) $price->unit_price, 4) }} {{ $price->currency }}</td>
                    <td>{{ $price->valid_from?->toDateString() }} - {{ $price->valid_to?->toDateString() ?? 'Open ended' }}</td>
                    <td><x-supply.status-badge :status="$price->status" /></td>
                    <td>{{ $price->createdBy?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No supplier product prices yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $prices->links() }}
</section>
@endsection
