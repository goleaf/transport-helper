@extends('layouts.app')

@section('title')
Edit Replenishment Profile
@endsection

@section('content')
<x-supply.page-header title="Edit Replenishment Profile" :subtitle="$profile->name" :back-url="route('supply.forecasting.profiles.show', $profile)" />

<section>
    <form method="POST" action="{{ route('supply.forecasting.profiles.update', $profile) }}" class="form-grid">
        @csrf
        @method('PATCH')

        @include('supply.forecasting.profiles.form-fields')

        <div class="form-actions">
            <x-supply.button type="submit">Save profile</x-supply.button>
        </div>
    </form>
</section>
@endsection
