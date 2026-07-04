@extends('layouts.app')

@section('title')
Supplier Product Mappings
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Supplier Product Identity Mappings</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<x-supply.alert tone="warning">
    Pending mappings are not used as final product identity matches until approved.
</x-supply.alert>

<section>
    <h2>Create Mapping</h2>
    <form method="POST" action="{{ route('supply.master-data.supplier-product-identities.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Company
            <select name="company_id" class="select select-bordered" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Supplier
            <select name="supplier_id" class="select select-bordered" required>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Product
            <select name="product_id" class="select select-bordered" required>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Supplier SKU
            <input class="input input-bordered" name="supplier_sku" value="{{ old('supplier_sku') }}">
        </label>
        <label>Manufacturer SKU
            <input class="input input-bordered" name="manufacturer_sku" value="{{ old('manufacturer_sku') }}">
        </label>
        <label>Barcode
            <input class="input input-bordered" name="barcode" value="{{ old('barcode') }}">
        </label>
        <label class="md:col-span-2">Supplier product name
            <input class="input input-bordered" name="supplier_product_name" value="{{ old('supplier_product_name') }}">
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Create mapping</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Mappings</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Product</th>
                <th>Supplier SKU</th>
                <th>Manufacturer SKU</th>
                <th>Barcode</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($identities as $identity)
                <tr>
                    <td>{{ $identity->supplier?->name }}</td>
                    <td>{{ $identity->product?->sku }} - {{ $identity->product?->name }}</td>
                    <td>{{ $identity->supplier_sku ?: 'Not set' }}</td>
                    <td>{{ $identity->manufacturer_sku ?: 'Not set' }}</td>
                    <td>{{ $identity->barcode ?: 'Not set' }}</td>
                    <td><x-supply.status-badge :status="$identity->status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No supplier product mappings yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $identities->links() }}
</section>
@endsection
