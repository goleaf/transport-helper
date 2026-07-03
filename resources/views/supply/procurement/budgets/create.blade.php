@extends('layouts.app')

@section('title')
Create Procurement Budget
@endsection

@section('content')
<x-supply.page-header title="Create Procurement Budget" subtitle="Budget period and controlled spend envelope." :back-url="route('supply.procurement.budgets.index')" />

@include('supply.procurement.partials.tabs')

<section>
    <form method="POST" action="{{ route('supply.procurement.budgets.store') }}" class="form-grid">
        @csrf

        @include('supply.procurement.partials.budget-form', ['budget' => null])

        <div class="form-actions">
            <x-supply.button type="submit">Create budget</x-supply.button>
        </div>
    </form>
</section>
@endsection
