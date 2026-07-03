<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Supply Import</title>
</head>
<body>
    <main>
        <x-supply.navigation />

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
                Supplier
                <select name="supplier_id">
                    <option value="">Optional</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <div>{{ $errors->first('supplier_id') }}</div>

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
                    @foreach ($adapters as $adapter)
                        <option value="{{ $adapter }}" @selected(old('adapter', 'csv') === $adapter)>
                            {{ $adapter }}
                        </option>
                    @endforeach
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
                <input type="hidden" name="dry_run" value="0">
                <input type="checkbox" name="dry_run" value="1" @checked(old('dry_run'))>
            </label>

            <label>
                Allow duplicate checksum
                <input type="hidden" name="allow_duplicate" value="0">
                <input type="checkbox" name="allow_duplicate" value="1" @checked(old('allow_duplicate'))>
            </label>

            <label>
                Allow negative stock
                <input type="hidden" name="allow_negative_stock" value="0">
                <input type="checkbox" name="allow_negative_stock" value="1" @checked(old('allow_negative_stock'))>
            </label>

            <label>
                Delimiter
                <input name="delimiter" value="{{ old('delimiter', ',') }}">
            </label>
            <div>{{ $errors->first('delimiter') }}</div>

            <label>
                Has header
                <input type="hidden" name="has_header" value="0">
                <input type="checkbox" name="has_header" value="1" @checked(old('has_header', true))>
            </label>

            <label>
                Date format
                <input name="date_format" value="{{ old('date_format') }}">
            </label>
            <div>{{ $errors->first('date_format') }}</div>

            <label>
                File
                <input type="file" name="file">
            </label>
            <div>{{ $errors->first('file') }}</div>

            <button type="submit">Run import</button>
        </form>
    </main>
</body>
</html>
