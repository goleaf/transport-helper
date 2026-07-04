@extends('layouts.app')

@section('title')
Product Aliases
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Product Aliases</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section>
    <h2>Create Product Alias</h2>
    <form method="POST" action="{{ route('supply.master-data.product-aliases.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Company
            <select name="company_id" class="select select-bordered" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
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
        <label>Alias
            <input class="input input-bordered" name="alias" value="{{ old('alias') }}" required>
        </label>
        <label>Alias type
            <input class="input input-bordered" name="alias_type" value="{{ old('alias_type', 'sku_alias') }}">
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Create alias</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Aliases</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Alias</th>
                <th>Product</th>
                <th>Type</th>
                <th>Status</th>
                <th>Source</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($aliases as $alias)
                <tr>
                    <td><strong>{{ $alias->alias }}</strong></td>
                    <td>{{ $alias->product?->sku }} - {{ $alias->product?->name }}</td>
                    <td><x-supply.human-label :label="$alias->alias_type" /></td>
                    <td><x-supply.status-badge :status="$alias->status" /></td>
                    <td>{{ $alias->source_type ?: 'Manual' }}</td>
                    <td>{{ $alias->created_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No product aliases yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $aliases->links() }}
</section>
@endsection
