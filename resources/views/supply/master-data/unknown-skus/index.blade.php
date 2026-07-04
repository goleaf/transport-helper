@extends('layouts.app')

@section('title')
Unknown SKUs
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Unknown SKU Resolution</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<x-supply.alert tone="warning">
    Unknown SKU records cannot create products automatically. Use mapping, alias creation or an approved change request.
</x-supply.alert>

<section>
    <h2>Record Unknown SKU</h2>
    <form method="POST" action="{{ route('supply.master-data.unknown-skus.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Company
            <select name="company_id" class="select select-bordered" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Supplier
            <select name="supplier_id" class="select select-bordered">
                <option value="">No supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Unknown SKU
            <input class="input input-bordered" name="unknown_sku" value="{{ old('unknown_sku') }}" required>
        </label>
        <label>Source type
            <input class="input input-bordered" name="source_type" value="{{ old('source_type', 'manual') }}">
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Record unknown SKU</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Unknown SKUs</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Supplier</th>
                <th>Source</th>
                <th>Status</th>
                <th>Resolution</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resolutions as $resolution)
                <tr>
                    <td><strong>{{ $resolution->unknown_sku }}</strong></td>
                    <td>{{ $resolution->supplier?->name ?: 'No supplier' }}</td>
                    <td>{{ $resolution->source_type ?: 'Manual' }}</td>
                    <td><x-supply.status-badge :status="$resolution->status" /></td>
                    <td>{{ $resolution->resolvedProduct?->sku ?: ($resolution->resolution_type ?: 'Unresolved') }}</td>
                    <td><x-supply.table-action :href="route('supply.master-data.unknown-skus.show', $resolution)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No unknown SKUs recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $resolutions->links() }}
</section>
@endsection
