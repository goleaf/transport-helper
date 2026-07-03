@extends('layouts.app')

@section('title')
Procurement Budgets
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Procurement Budgets</h1>
    </div>
    <x-supply.button :href="route('supply.procurement.budgets.create')">Create budget</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Period</th>
                <th>Total</th>
                <th>Lines</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($budgets as $budget)
                <tr>
                    <td><strong>{{ $budget->name }}</strong></td>
                    <td>{{ $budget->company?->name }}</td>
                    <td>{{ $budget->date_from?->toDateString() }} - {{ $budget->date_to?->toDateString() }}</td>
                    <td>{{ number_format((float) $budget->total_amount, 2) }} {{ $budget->currency }}</td>
                    <td>{{ $budget->lines_count }}</td>
                    <td><x-supply.status-badge :status="$budget->status" /></td>
                    <td>{{ $budget->owner?->name ?? 'No owner' }}</td>
                    <td><x-supply.table-action :href="route('supply.procurement.budgets.show', $budget)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No procurement budgets yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $budgets->links() }}
</section>
@endsection
