@extends('layouts.app')

@section('title')
Create Replenishment Profile
@endsection

@section('content')
<x-supply.page-header title="Create Replenishment Profile" subtitle="Deterministic safety and exclusion rules for scenario inputs." :back-url="route('supply.forecasting.profiles.index')" />

<section>
    <form method="POST" action="{{ route('supply.forecasting.profiles.store') }}" class="form-grid">
        @csrf

        @include('supply.forecasting.profiles.form-fields', ['profile' => null])

        <div class="form-actions">
            <x-supply.button type="submit">Create profile</x-supply.button>
        </div>
    </form>
</section>
@endsection
