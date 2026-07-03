<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Order {{ $order->order_number }}</title>
</head>
<body>
    <main>
        <header>
            <p><a href="{{ route('supply.supplier-orders.index') }}">Back to supplier orders</a></p>
            <h1>Supplier Order {{ $order->order_number }}</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <section>
                <h2>Errors</h2>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <dl>
                <dt>Supplier</dt>
                <dd>{{ $order->supplier?->name }}</dd>

                <dt>Status</dt>
                <dd>{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</dd>

                <dt>Order date</dt>
                <dd>{{ $order->order_date?->toDateString() }}</dd>

                <dt>Provider message id</dt>
                <dd>{{ $order->email_message_id }}</dd>
            </dl>
        </section>

        <section>
            <h2>Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->items as $item)
                        <tr>
                            <td>{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->ordered_quantity }}</td>
                            <td>{{ $item->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Exports</h2>
            @if ($canExport)
                <form method="post" action="{{ route('supply.supplier-orders.export', $order) }}">
                    @csrf
                    <label for="format">Format</label>
                    <select id="format" name="format">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                        <option value="excel_csv">Excel-compatible CSV</option>
                    </select>
                    <button type="submit">Export</button>
                </form>
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Filename</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exportFiles as $exportFile)
                        <tr>
                            <td>{{ $exportFile->export_type }}</td>
                            <td>{{ $exportFile->filename }}</td>
                            <td>{{ $exportFile->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No exports.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Email workflow</h2>
            @if ($canPrepareEmail)
                <form method="post" action="{{ route('supply.supplier-orders.prepare-email', $order) }}">
                    @csrf
                    <button type="submit">Prepare email</button>
                </form>
            @endif

            @if ($canApproveEmail)
                <form method="post" action="{{ route('supply.supplier-orders.approve-email', $order) }}">
                    @csrf
                    <button type="submit">Approve email</button>
                </form>
            @endif

            @if ($canSendEmail)
                <form method="post" action="{{ route('supply.supplier-orders.send-email', $order) }}">
                    @csrf
                    <label for="no_attachment_confirmed">
                        <input id="no_attachment_confirmed" name="no_attachment_confirmed" type="checkbox" value="1">
                        Send without attachment
                    </label>
                    <button type="submit">Send email</button>
                </form>
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Direction</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Message id</th>
                        <th>Sent at</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->emailMessages as $emailMessage)
                        <tr>
                            <td>{{ $emailMessage->direction instanceof \BackedEnum ? $emailMessage->direction->value : $emailMessage->direction }}</td>
                            <td>{{ $emailMessage->subject }}</td>
                            <td>{{ $emailMessage->status }}</td>
                            <td>{{ $emailMessage->message_id }}</td>
                            <td>{{ $emailMessage->sent_at?->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No email messages.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Logistics</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Order date</th>
                        <th>Ready date</th>
                        <th>Pickup date</th>
                        <th>Delivery date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->logisticsRecords as $record)
                        <tr>
                            <td>{{ $record->status instanceof \BackedEnum ? $record->status->value : $record->status }}</td>
                            <td>{{ $record->order_date?->toDateString() }}</td>
                            <td>{{ $record->ready_date?->toDateString() }}</td>
                            <td>{{ $record->pickup_date?->toDateString() }}</td>
                            <td>{{ $record->delivery_date?->toDateString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No logistics record.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
