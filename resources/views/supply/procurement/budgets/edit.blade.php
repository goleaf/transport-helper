@extends('layouts.app')

@section('title')
Edit Procurement Budget
@endsection

@section('content')
<x-supply.page-header :title="$budget->name" subtitle="Edit budget period and amount." :back-url="route('supply.procurement.budgets.show', $budget)" />

@include('supply.procurement.partials.tabs')

<section>
    <form method="POST" action="{{ route('supply.procurement.budgets.update', $budget) }}" class="form-grid">
        @csrf
        @method('PATCH')

        @include('supply.procurement.partials.budget-form', ['budget' => $budget])

        <div class="form-actions">
            <x-supply.button type="submit">Save budget</x-supply.button>
        </div>
    </form>
</section>
@endsection
