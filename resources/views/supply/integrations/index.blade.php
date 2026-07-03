@extends('layouts.app')

@section('title')
Integrations
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Integrations</h1>
                <p class="text-sm text-gray-600">External integrations are disabled by default and require approval before activation.</p>
            </div>
            <x-supply.button :href="route('supply.integrations.create')">New integration</x-supply.button>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="table table-zebra">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Environment</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Approval</th>
                        <th class="px-4 py-3">Last test</th>
                        <th class="px-4 py-3">External</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($connections as $connection)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $connection->name }}</td>
                            <td class="px-4 py-3">{{ $connection->provider }}</td>
                            <td class="px-4 py-3">{{ $connection->environment }}</td>
                            <td class="px-4 py-3">@include('supply.integrations.partials.status-badge', ['status' => $connection->status])</td>
                            <td class="px-4 py-3">{{ $connection->approval_status ?? 'not submitted' }}</td>
                            <td class="px-4 py-3">{{ $connection->last_test_status ?? 'not tested' }}</td>
                            <td class="px-4 py-3">{{ $connection->is_external ? 'yes' : 'no' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-supply.table-action :href="route('supply.integrations.show', $connection)" label="Open" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">No integrations configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $connections->links() }}
    </div>
@endsection
