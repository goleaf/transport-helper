<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Orders</title>
</head>
<body>
    <main>
        <header>
            <h1>Supplier Orders</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Order number</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Order date</th>
                    <th>Items</th>
                    <th>Email messages</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->supplier?->name }}</td>
                        <td>{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</td>
                        <td>{{ $order->order_date?->toDateString() }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td>{{ $order->email_messages_count }}</td>
                        <td><a href="{{ route('supply.supplier-orders.show', $order) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No supplier orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $orders->links() }}
    </main>
</body>
</html>
