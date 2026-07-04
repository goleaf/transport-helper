@extends('layouts.app')

@section('title')
Master Data Change Requests
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Change Requests</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section>
    <h2>Create Change Request</h2>
    <form method="POST" action="{{ route('supply.master-data.change-requests.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Company
            <select name="company_id" class="select select-bordered" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Type
            <select name="request_type" class="select select-bordered" required>
                <option value="create_product">Create product</option>
                <option value="update_product">Update product</option>
                <option value="create_supplier">Create supplier</option>
                <option value="update_supplier">Update supplier</option>
                <option value="create_alias">Create alias</option>
                <option value="supplier_product_mapping">Supplier product mapping</option>
                <option value="lifecycle_change">Lifecycle change</option>
                <option value="merge_request">Merge request</option>
                <option value="other">Other</option>
            </select>
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Create request</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Requests</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Status</th>
                <th>Requested by</th>
                <th>Reason</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $changeRequest)
                <tr>
                    <td><x-supply.human-label :label="$changeRequest->request_type" /></td>
                    <td><x-supply.status-badge :status="$changeRequest->status" /></td>
                    <td>{{ $changeRequest->requestedBy?->name ?: 'System' }}</td>
                    <td>{{ $changeRequest->reason }}</td>
                    <td>{{ $changeRequest->created_at?->diffForHumans() }}</td>
                    <td><x-supply.table-action :href="route('supply.master-data.change-requests.show', $changeRequest)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No change requests yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $requests->links() }}
</section>
@endsection
