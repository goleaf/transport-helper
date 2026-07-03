@extends('layouts.app')

@section('title')
Pilot Supplier Detail
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $pilot->name }}</h1>
                <p class="text-sm text-gray-600">{{ $pilot->supplier?->name }} · {{ $pilot->company?->name }}</p>
            </div>
            <x-supply.button :href="route('supply.pilots.edit', $pilot)" mode="outline" variant="neutral">Edit</x-supply.button>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Pilot files stay in private storage. Pilot approval does not activate integrations, send real email, call external APIs, call external AI, or select carriers automatically.
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Status</div>
                <div class="mt-2">@include('supply.pilots.partials.status-badge', ['status' => $pilot->status])</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Readiness</div>
                <div class="mt-2 font-medium text-gray-900">{{ $pilot->readiness_result_json['status'] ?? 'not run' }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Latest dry-run</div>
                <div class="mt-2 font-medium text-gray-900">{{ $pilot->dry_run_result_json['status'] ?? 'not run' }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs uppercase text-gray-500">Live ready</div>
                <div class="mt-2 font-medium text-gray-900">{{ $uatEvaluation['live_ready'] ? 'yes' : 'no' }}</div>
            </div>
        </div>

        @include('supply.pilots.partials.files-panel', ['pilot' => $pilot])
        @include('supply.pilots.partials.mapping-panel', ['pilot' => $pilot])
        @include('supply.pilots.partials.readiness-panel', ['pilot' => $pilot])
        @include('supply.pilots.partials.dry-run-panel', ['pilot' => $pilot])
        @include('supply.pilots.partials.uat-checklist-panel', ['pilot' => $pilot, 'checklist' => $checklist, 'evaluation' => $uatEvaluation])
        @include('supply.pilots.partials.approval-panel', ['pilot' => $pilot])
        @include('supply.pilots.partials.report-panel', ['pilot' => $pilot])
    </div>
@endsection
