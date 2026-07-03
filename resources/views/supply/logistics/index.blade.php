<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logistics Records</title>
</head>
<body>
    <main>
        <header>
            <h1>Logistics Records</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <section>
                <h2>Errors</h2>
                <ul>
                    @forelse ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @empty
                        <li>No errors.</li>
                    @endforelse
                </ul>
            </section>
        @endif

        <section>
            <h2>Filters</h2>
            <form method="get" action="{{ route('supply.logistics.index') }}">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @forelse ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->value }}</option>
                    @empty
                        <option value="" disabled>No statuses.</option>
                    @endforelse
                </select>
                <button type="submit">Filter</button>
            </form>
        </section>

        <section>
            <h2>Export and Sync</h2>
            <form method="post" action="{{ route('supply.logistics.export') }}">
                @csrf
                @if ($selectedStatus !== '')
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                @endif
                <button type="submit">Export CSV</button>
            </form>

            <form method="post" action="{{ route('supply.logistics.sync.google-sheets') }}">
                @csrf
                <button type="submit">Sync Google Sheets</button>
            </form>
        </section>

        <section>
            <h2>Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Supplier order</th>
                        <th>Supplier</th>
                        <th>Carrier</th>
                        <th>Order date</th>
                        <th>Confirmation date</th>
                        <th>Ready date</th>
                        <th>Pickup date</th>
                        <th>Delivery date</th>
                        <th>Actual received</th>
                        <th>Transport price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->supplierOrder?->order_number }}</td>
                            <td>{{ $record->supplier?->name }}</td>
                            <td>{{ $record->carrier?->name }}</td>
                            <td>{{ $record->order_date?->toDateString() }}</td>
                            <td>{{ $record->confirmation_date?->toDateString() }}</td>
                            <td>{{ $record->ready_date?->toDateString() }}</td>
                            <td>{{ $record->pickup_date?->toDateString() }}</td>
                            <td>{{ $record->delivery_date?->toDateString() }}</td>
                            <td>{{ $record->actual_received_date?->toDateString() }}</td>
                            <td>{{ $record->transport_price }} {{ $record->currency }}</td>
                            <td>{{ $record->status instanceof \BackedEnum ? $record->status->value : $record->status }}</td>
                            <td><a href="{{ route('supply.logistics.show', $record) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">No logistics records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $records->links() }}
        </section>
    </main>
</body>
</html>
