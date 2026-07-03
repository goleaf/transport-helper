<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Emails</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>Supply Emails</h1>
            <p><a href="{{ route('supply.emails.create-manual') }}">Manual inbound email</a></p>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="get" action="{{ route('supply.emails.index') }}">
            <label>
                Direction
                <select name="direction">
                    <option value="">Any</option>
                    <option value="inbound" @selected(request('direction') === 'inbound')>Inbound</option>
                    <option value="outbound" @selected(request('direction') === 'outbound')>Outbound</option>
                </select>
            </label>

            <label>
                Status
                <input name="status" value="{{ request('status') }}">
            </label>

            <label>
                From email
                <input name="from_email" value="{{ request('from_email') }}">
            </label>

            <label>
                Needs review
                <input type="checkbox" name="needs_review" value="1" @checked(request()->boolean('needs_review'))>
            </label>

            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Direction</th>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Supplier</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>AI extractions</th>
                    <th>Attachments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($emails as $email)
                    <tr>
                        <td>{{ $email->direction instanceof \BackedEnum ? $email->direction->value : $email->direction }}</td>
                        <td>{{ $email->from_email }}</td>
                        <td>{{ $email->subject }}</td>
                        <td>{{ $email->relatedSupplier?->name }}</td>
                        <td>{{ $email->relatedSupplierOrder?->order_number }}</td>
                        <td>{{ $email->status }}</td>
                        <td>{{ $email->ai_email_extractions_count }}</td>
                        <td>{{ $email->attachments_count }}</td>
                        <td><a href="{{ route('supply.emails.show', $email) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No emails yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $emails->links() }}
    </main>
</body>
</html>
