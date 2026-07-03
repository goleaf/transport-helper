@extends('layouts.app')

@section('title')
New Integration
@endsection

@section('content')
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">New Integration</h1>

        <form method="POST" action="{{ route('supply.integrations.store') }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @include('supply.integrations.partials.form', ['connection' => null, 'maskedConfig' => [], 'configText' => $configText])
            <x-supply.button type="submit">Save integration</x-supply.button>
        </form>
    </div>
@endsection
