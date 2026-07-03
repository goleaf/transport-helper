<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Reports</h2>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach (['readiness', 'uat'] as $reportType)
            @foreach (['csv', 'json'] as $format)
                <form method="POST" action="{{ route('supply.pilots.reports.export', $pilot) }}">
                    @csrf
                    <input type="hidden" name="report_type" value="{{ $reportType }}">
                    <input type="hidden" name="format" value="{{ $format }}">
                    <x-supply.button type="submit" mode="outline" variant="neutral">{{ strtoupper($format) }} {{ $reportType }}</x-supply.button>
                </form>
            @endforeach
        @endforeach
    </div>
</div>
