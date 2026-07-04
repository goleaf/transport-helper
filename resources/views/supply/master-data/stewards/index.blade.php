@extends('layouts.app')

@section('title')
Data Stewards
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Data Steward Assignments</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section>
    <h2>Assign Steward</h2>
    <form method="POST" action="{{ route('supply.master-data.stewards.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Company
            <select name="company_id" class="select select-bordered" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>User
            <select name="user_id" class="select select-bordered" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                @endforeach
            </select>
        </label>
        <label>Stewardship type
            <input class="input input-bordered" name="stewardship_type" value="{{ old('stewardship_type', 'category') }}" required>
        </label>
        <label>Category
            <input class="input input-bordered" name="category" value="{{ old('category') }}">
        </label>
        <label>Supplier
            <select name="supplier_id" class="select select-bordered">
                <option value="">No supplier scope</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Product
            <select name="product_id" class="select select-bordered">
                <option value="">No product scope</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="md:col-span-2">Notes
            <textarea class="textarea textarea-bordered" name="notes">{{ old('notes') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Assign steward</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Assignments</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>User</th>
                <th>Type</th>
                <th>Supplier</th>
                <th>Product</th>
                <th>Category</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->user?->name }}</td>
                    <td><x-supply.human-label :label="$assignment->stewardship_type" /></td>
                    <td>{{ $assignment->supplier?->name ?: 'Any supplier' }}</td>
                    <td>{{ $assignment->product?->sku ?: 'Any product' }}</td>
                    <td>{{ $assignment->category ?: 'Any category' }}</td>
                    <td>{{ $assignment->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No steward assignments yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $assignments->links() }}
</section>
@endsection
