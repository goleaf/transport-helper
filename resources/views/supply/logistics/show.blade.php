<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logistics Record {{ $record->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.logistics.index') }}">Back to logistics</a></p>
            <h1>Logistics Record {{ $record->id }}</h1>
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
            <h2>Details</h2>
            <dl>
                <dt>Company</dt>
                <dd>{{ $record->company?->name }}</dd>

                <dt>Supplier order</dt>
                <dd>{{ $record->supplierOrder?->order_number }}</dd>

                <dt>Supplier</dt>
                <dd>{{ $record->supplier?->name }}</dd>

                <dt>Carrier</dt>
                <dd>{{ $record->carrier?->name }}</dd>

                <dt>Order date</dt>
                <dd>{{ $record->order_date?->toDateString() }}</dd>

                <dt>Confirmation date</dt>
                <dd>{{ $record->confirmation_date?->toDateString() }}</dd>

                <dt>Ready date</dt>
                <dd>{{ $record->ready_date?->toDateString() }}</dd>

                <dt>Pickup date</dt>
                <dd>{{ $record->pickup_date?->toDateString() }}</dd>

                <dt>Delivery date</dt>
                <dd>{{ $record->delivery_date?->toDateString() }}</dd>

                <dt>Actual received date</dt>
                <dd>{{ $record->actual_received_date?->toDateString() }}</dd>

                <dt>Transport price</dt>
                <dd>{{ $record->transport_price }} {{ $record->currency }}</dd>

                <dt>Status</dt>
                <dd>{{ $record->status instanceof \BackedEnum ? $record->status->value : $record->status }}</dd>

                <dt>External sheet reference</dt>
                <dd>{{ $record->external_sheet_reference }}</dd>

                <dt>Notes</dt>
                <dd>{{ $record->notes }}</dd>
            </dl>
        </section>

        <section>
            <h2>Update Status</h2>
            <form method="post" action="{{ route('supply.logistics.update-status', $record) }}">
                @csrf
                <label for="status">Status</label>
                <select id="status" name="status">
                    @forelse ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($record->status instanceof \BackedEnum && $record->status->value === $status->value)>{{ $status->value }}</option>
                    @empty
                        <option value="" disabled>No statuses.</option>
                    @endforelse
                </select>
                <button type="submit">Update status</button>
            </form>
        </section>

        <section>
            <h2>Audit History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>Old values</th>
                        <th>New values</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->created_at?->toDateTimeString() }}</td>
                            <td>{{ $auditLog->event_type }}</td>
                            <td>{{ $auditLog->user?->name }}</td>
                            <td><pre>{{ json_encode($auditLog->old_values_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                            <td><pre>{{ json_encode($auditLog->new_values_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No audit logs.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
