@extends('layouts.app')

@section('title')
Integration Detail
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $connection->name }}</h1>
                <p class="text-sm text-gray-600">{{ $connection->provider }} · {{ $connection->environment }}</p>
            </div>
            <x-supply.button :href="route('supply.integrations.edit', $connection)" mode="outline" variant="neutral">Edit</x-supply.button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Status</div>
                <div class="mt-2">@include('supply.integrations.partials.status-badge', ['status' => $connection->status])</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Approval</div>
                <div class="mt-2 font-medium text-gray-900">{{ $connection->approval_status ?? 'pending' }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Last test</div>
                <div class="mt-2 font-medium text-gray-900">{{ $connection->last_test_status ?? 'not tested' }}</div>
            </div>
        </div>

        @include('supply.integrations.partials.masked-config', ['maskedConfigLines' => $maskedConfigLines])
        @include('supply.integrations.partials.approval-panel', ['connection' => $connection])
        @include('supply.integrations.partials.test-panel', ['connection' => $connection])
    </div>
@endsection
