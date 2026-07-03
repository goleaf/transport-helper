<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Order {{ $order->order_number }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.supplier-orders.index') }}">Back to supplier orders</a></p>
            <h1>Supplier Order {{ $order->order_number }}</h1>
            @include('supply.supplier-orders.partials.status-badge', ['status' => $order->status])
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
                <dt>Order date</dt>
                <dd>{{ $order->order_date?->toDateString() }}</dd>
                <dt>Created from proposal</dt>
                <dd>
                    @if ($order->orderProposal)
                        <a href="{{ route('supply.proposals.show', $order->orderProposal) }}">Proposal #{{ $order->orderProposal->id }}</a>
                    @else
                        Not linked
                    @endif
                </dd>
                <dt>Email approved by</dt>
                <dd>{{ $order->emailApprovedBy?->name ?? 'Not approved' }}</dd>
                <dt>Email approved at</dt>
                <dd>{{ $order->email_approved_at?->toDateTimeString() ?? 'Not approved' }}</dd>
                <dt>Sent by</dt>
                <dd>{{ $order->sentBy?->name ?? 'Not sent' }}</dd>
                <dt>Sent at</dt>
                <dd>{{ $order->sent_at?->toDateTimeString() ?? 'Not sent' }}</dd>
            </dl>
        </section>

        <section>
            <h2>Summary</h2>
            <dl>
                <dt>Items count</dt>
                <dd>{{ $itemsCount }}</dd>
                <dt>Total ordered quantity</dt>
                <dd>{{ $totalOrderedQuantity }}</dd>
                <dt>Latest export</dt>
                <dd>{{ $exportFiles->first()?->filename ?? 'No exports' }}</dd>
                <dt>Email status</dt>
                <dd>{{ $emailMessage?->status ?? 'No draft' }}</dd>
                <dt>Logistics status</dt>
                <dd>{{ $firstLogisticsRecord?->status instanceof \BackedEnum ? $firstLogisticsRecord?->status->value : ($firstLogisticsRecord?->status ?? 'No logistics record') }}</dd>
            </dl>
        </section>

        @include('supply.supplier-orders.partials.items-table', ['order' => $order])
        @include('supply.supplier-orders.partials.export-panel', ['order' => $order, 'exportFiles' => $exportFiles, 'canExport' => $canExport])
        @include('supply.supplier-orders.partials.email-panel', [
            'order' => $order,
            'emailMessage' => $emailMessage,
            'canPrepareEmail' => $canPrepareEmail,
            'canApproveEmail' => $canApproveEmail,
            'canSendEmail' => $canSendEmail,
        ])
        @include('supply.supplier-orders.partials.audit-history', ['auditLogs' => $auditLogs])
    </main>
</body>
</html>
