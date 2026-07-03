@extends('layouts.app')

@section('title')
Edit Integration
@endsection

@section('content')
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">Edit Integration</h1>

        <form method="POST" action="{{ route('supply.integrations.update', $connection) }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PATCH')
            @include('supply.integrations.partials.form', ['connection' => $connection, 'maskedConfig' => $maskedConfig, 'configText' => $configText])
            <x-supply.button type="submit">Update integration</x-supply.button>
        </form>
    </div>
@endsection
