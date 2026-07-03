<section>
    <h2>Filters</h2>
    <form method="GET" action="{{ route('supply.analytics.reports.show', ['reportType' => $reportType]) }}">
        <label>
            Date from
            <input class="input input-bordered input-primary" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </label>
        <label>
            Date to
            <input class="input input-bordered input-primary" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </label>
        <label>
            Supplier ID
            <input class="input input-bordered input-primary" name="supplier_id" value="{{ $filters['supplier_id'] ?? '' }}">
        </label>
        <label>
            Carrier ID
            <input class="input input-bordered input-primary" name="carrier_id" value="{{ $filters['carrier_id'] ?? '' }}">
        </label>
        <x-supply.button type="submit">Run report</x-supply.button>
    </form>
</section>
