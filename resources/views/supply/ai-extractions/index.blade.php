<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Email Extractions</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>AI Email Extractions</h1>
        </header>

        <form method="get" action="{{ route('supply.ai-extractions.index') }}">
            <label>
                Provider
                <input name="provider" value="{{ request('provider') }}">
            </label>
            <label>
                Requires review
                <input type="checkbox" name="requires_human_review" value="1" @checked(request()->boolean('requires_human_review'))>
            </label>
            <label>
                Accepted
                <input type="checkbox" name="accepted" value="1" @checked(request()->boolean('accepted'))>
            </label>
            <label>
                Rejected
                <input type="checkbox" name="rejected" value="1" @checked(request()->boolean('rejected'))>
            </label>
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email subject</th>
                    <th>Provider</th>
                    <th>Email type</th>
                    <th>Confidence</th>
                    <th>Review</th>
                    <th>Review reason</th>
                    <th>Accepted</th>
                    <th>Rejected</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($extractions as $extraction)
                    <tr>
                        <td>{{ $extraction->id }}</td>
                        <td>{{ $extraction->emailMessage?->subject }}</td>
                        <td>{{ $extraction->provider }}</td>
                        <td>{{ $extraction->output_json['email_type'] ?? 'unclear' }}</td>
                        <td>{{ $extraction->confidence }}</td>
                        <td>{{ $extraction->requires_human_review ? 'Needs review' : 'Reviewed' }}</td>
                        <td>{{ $extraction->review_reason }}</td>
                        <td>{{ $extraction->accepted_at }}</td>
                        <td>{{ $extraction->rejected_at }}</td>
                        <td><a href="{{ route('supply.ai-extractions.show', $extraction) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No AI email extractions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $extractions->links() }}
    </main>
</body>
</html>
