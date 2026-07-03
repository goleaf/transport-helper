@extends('layouts.app')

@section('title')
Pilot Suppliers
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Pilot Suppliers</h1>
                <p class="text-sm text-gray-600">One-supplier pilot onboarding with private files, dry-runs, UAT and approval.</p>
            </div>
            <x-supply.button :href="route('supply.pilots.create')">New pilot</x-supply.button>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="table table-zebra">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Files</th>
                        <th class="px-4 py-3">Runs</th>
                        <th class="px-4 py-3">Readiness</th>
                        <th class="px-4 py-3">Approved by</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pilots as $pilot)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $pilot->supplier?->name }}</td>
                            <td class="px-4 py-3">{{ $pilot->company?->name }}</td>
                            <td class="px-4 py-3">@include('supply.pilots.partials.status-badge', ['status' => $pilot->status])</td>
                            <td class="px-4 py-3">{{ $pilot->files_count }}</td>
                            <td class="px-4 py-3">{{ $pilot->runs_count }}</td>
                            <td class="px-4 py-3">{{ $pilot->readiness_result_json['status'] ?? 'not run' }}</td>
                            <td class="px-4 py-3">{{ $pilot->approvedBy?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-supply.table-action :href="route('supply.pilots.show', $pilot)" label="Open" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">No pilot suppliers configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pilots->links() }}
    </div>
@endsection
