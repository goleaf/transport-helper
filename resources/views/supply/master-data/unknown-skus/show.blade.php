@extends('layouts.app')

@section('title')
Unknown SKU {{ $resolution->unknown_sku }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Unknown SKU</p>
        <h1>{{ $resolution->unknown_sku }}</h1>
    </div>
    <x-supply.button :href="route('supply.master-data.unknown-skus.index')">Back to unknown SKUs</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section class="grid gap-4 md:grid-cols-2">
    <x-supply.card>
        <h2>Current Status</h2>
        <p><strong>Status:</strong> <x-supply.status-badge :status="$resolution->status" /></p>
        <p><strong>Supplier:</strong> {{ $resolution->supplier?->name ?: 'No supplier' }}</p>
        <p><strong>Source:</strong> {{ $resolution->source_type ?: 'Manual' }}</p>
        <p><strong>Reason:</strong> {{ $resolution->reason ?: 'Not resolved yet' }}</p>
    </x-supply.card>
    <x-supply.card>
        <h2>Safety Boundary</h2>
        <p>Mapping requires a reason. Product creation is only possible through an approved change request.</p>
        <p>AI suggestions require human approval and never become aliases automatically.</p>
    </x-supply.card>
</section>

<section>
    <h2>Resolve</h2>
    <form method="POST" action="{{ route('supply.master-data.unknown-skus.resolve', $resolution) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Resolution type
            <select name="resolution_type" class="select select-bordered" required>
                <option value="existing_product">Map to existing product</option>
                <option value="product_alias">Create product alias</option>
                <option value="product_change_request">Create product change request</option>
                <option value="ignored">Ignore with reason</option>
            </select>
        </label>
        <label>Product
            <select name="product_id" class="select select-bordered">
                <option value="">No product selected</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Alias type
            <input class="input input-bordered" name="alias_type" value="{{ old('alias_type', 'sku_alias') }}">
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Save resolution</x-supply.button>
        </div>
    </form>
</section>
@endsection
