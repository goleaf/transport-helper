<section>
    <h2>Export</h2>
    <p>Exports are stored privately and do not include secrets or full email bodies.</p>
    <form method="POST" action="{{ route('supply.analytics.reports.export', ['reportType' => $reportType]) }}">
        @csrf
        <label>
            Format
            <select class="select select-bordered" name="format">
                <option value="csv">CSV</option>
                <option value="json">Data file</option>
            </select>
        </label>
        <x-supply.button type="submit">Export report</x-supply.button>
    </form>
</section>
