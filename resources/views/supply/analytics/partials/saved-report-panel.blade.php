<section>
    <h2>Save Report</h2>
    <form method="POST" action="{{ route('supply.analytics.saved-reports.store') }}">
        @csrf
        <input type="hidden" name="report_type" value="{{ $reportType }}">
        <label>
            Name
            <input class="input input-bordered input-primary" name="name" value="{{ $reportType }} saved report">
        </label>
        <label>
            Shared
            <input class="checkbox checkbox-primary" type="checkbox" name="is_shared" value="1">
        </label>
        <x-supply.button type="submit">Save report</x-supply.button>
    </form>
</section>
