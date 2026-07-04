@props(['product'])

<section>
    <h2>Product Lifecycle</h2>
    <form method="POST" action="{{ route('supply.master-data.products.lifecycle', $product) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Status
            <select name="status" class="select select-bordered" required>
                <option value="active">Active</option>
                <option value="blocked">Blocked</option>
                <option value="discontinued">Discontinued</option>
                <option value="replaced">Replaced</option>
                <option value="archived">Archived</option>
            </select>
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required></textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Update lifecycle</x-supply.button>
        </div>
    </form>
</section>
