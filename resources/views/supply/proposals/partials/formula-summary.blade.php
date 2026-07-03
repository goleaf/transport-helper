<section>
    <h2>Formula components</h2>
    <table class="table table-zebra">
        <tbody>
            <tr><th>trend</th><td>{{ $item->trend }}</td></tr>
            <tr><th>need_t0_t1</th><td>{{ $item->need_t0_t1 }}</td></tr>
            <tr><th>stock_t1</th><td>{{ $item->stock_t1 }}</td></tr>
            <tr><th>need_t1_t2</th><td>{{ $item->need_t1_t2 }}</td></tr>
            <tr><th>safety_stock</th><td>{{ $item->safety_stock }}</td></tr>
            <tr><th>inbound_until_t1</th><td>{{ $item->inbound_until_t1 }}</td></tr>
            <tr><th>inbound_t1_t3</th><td>{{ $item->inbound_t1_t3 }}</td></tr>
            <tr><th>reserved_quantity</th><td>{{ $item->reserved_quantity }}</td></tr>
            <tr><th>raw_need</th><td>{{ $item->raw_need }}</td></tr>
            <tr><th>moq_applied</th><td>{{ $item->moq_applied }}</td></tr>
            <tr><th>pack_multiple_applied</th><td>{{ $item->pack_multiple_applied }}</td></tr>
            <tr><th>pallet_quantity_applied</th><td>{{ $item->pallet_quantity_applied }}</td></tr>
            <tr><th>recommended_quantity</th><td>{{ $item->recommended_quantity }}</td></tr>
            <tr><th>approved_quantity</th><td>{{ $item->approved_quantity }}</td></tr>
            <tr><th>user_adjusted_quantity</th><td>{{ $item->user_adjusted_quantity }}</td></tr>
            <tr><th>adjustment_reason</th><td>{{ $item->adjustment_reason }}</td></tr>
        </tbody>
    </table>
</section>
