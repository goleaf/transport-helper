<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Supply Import</title>
</head>
<body>
    <main>
        <header>
            <h1>Create Supply Import</h1>
            <a href="{{ route('supply.imports.index') }}">Back</a>
        </header>

        <form method="POST" action="{{ route('supply.imports.store') }}" enctype="multipart/form-data">
            @csrf

            <label>
                Company
                <select name="company_id" required>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <div>{{ $errors->first('company_id') }}</div>

            <label>
                Import type
                <select name="import_type" required>
                    @foreach ($importTypes as $importType)
                        <option value="{{ $importType }}" @selected(old('import_type') === $importType)>
                            {{ $importType }}
                        </option>
                    @endforeach
                </select>
            </label>
            <div>{{ $errors->first('import_type') }}</div>

            <label>
                Adapter
                <select name="adapter" required>
                    <option value="csv" @selected(old('adapter', 'csv') === 'csv')>csv</option>
                    <option value="excel" @selected(old('adapter') === 'excel')>excel</option>
                    <option value="google_sheets" @selected(old('adapter') === 'google_sheets')>google_sheets</option>
                    <option value="api" @selected(old('adapter') === 'api')>api</option>
                    <option value="manual_json" @selected(old('adapter') === 'manual_json')>manual_json</option>
                    <option value="email_attachment" @selected(old('adapter') === 'email_attachment')>email_attachment</option>
                </select>
            </label>
            <div>{{ $errors->first('adapter') }}</div>

            <label>
                Source reference
                <input name="source_reference" value="{{ old('source_reference') }}">
            </label>
            <div>{{ $errors->first('source_reference') }}</div>

            <label>
                Dry run
                <input type="checkbox" name="dry_run" value="1" @checked(old('dry_run'))>
            </label>

            <label>
                File
                <input type="file" name="file" required>
            </label>
            <div>{{ $errors->first('file') }}</div>

            <button type="submit">Run import</button>
        </form>
    </main>
</body>
</html>
