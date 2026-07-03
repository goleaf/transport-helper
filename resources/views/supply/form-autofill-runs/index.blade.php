<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Autofill Runs</title>
</head>
<body>
    <main>
        <x-supply.navigation />
        <h1>Form Autofill Runs</h1>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Confidence</th>
                    <th>Fields</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td>{{ $run->id }}</td>
                        <td>{{ $run->emailMessage?->subject }}</td>
                        <td>{{ $run->formTemplate?->name }}</td>
                        <td>{{ $run->status instanceof \BackedEnum ? $run->status->value : $run->status }}</td>
                        <td>{{ $run->confidence }}</td>
                        <td>{{ $run->field_values_count }}</td>
                        <td><a href="{{ route('supply.form-autofill-runs.show', $run) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No form autofill runs.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $runs->links() }}
    </main>
</body>
</html>
