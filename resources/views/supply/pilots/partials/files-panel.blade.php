<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Pilot Files</h2>
    <form method="POST" action="{{ route('supply.pilots.files.upload', $pilot) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-4">
        @csrf
        <select name="file_type" class="select select-bordered select-primary">
            @foreach (['sales_history_sample','stock_snapshot_sample','inbound_orders_sample','reservations_sample','product_rules_sample','manufacturer_order_form','supplier_confirmation_email_sample','carrier_quote_email_sample','logistics_sheet_sample','other'] as $fileType)
                <option value="{{ $fileType }}">{{ $fileType }}</option>
            @endforeach
        </select>
        <input type="file" name="file" class="file-input file-input-bordered file-input-primary md:col-span-2">
        <x-supply.button type="submit">Upload</x-supply.button>
    </form>

    <div class="mt-4 overflow-hidden rounded border border-gray-200">
        <table class="table">
            <thead><tr><th>Type</th><th>File</th><th>Checksum</th><th>Uploaded</th><th></th></tr></thead>
            <tbody>
                @forelse ($pilot->files as $file)
                    <tr>
                        <td>{{ $file->file_type }}</td>
                        <td>{{ $file->original_filename }}</td>
                        <td class="font-mono text-xs">{{ $file->checksum }}</td>
                        <td>{{ $file->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('supply.pilots.files.delete', [$pilot, $file]) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="reason" value="Deleted from pilot file panel.">
                                <x-supply.button type="submit" mode="outline" variant="neutral">Delete</x-supply.button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-500">No pilot files uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
