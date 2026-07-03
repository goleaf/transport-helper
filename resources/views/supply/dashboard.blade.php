<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Dashboard</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>Supply Dashboard</h1>
        </header>

        <section id="replenishment-priorities">
            <h2>Replenishment Priorities</h2>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Recommended quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($replenishmentPriorities as $item)
                        <tr>
                            <td>{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->orderProposal?->supplier?->name }}</td>
                            <td>{{ $item->status instanceof \BackedEnum ? $item->status->value : $item->status }}</td>
                            <td>{{ $item->recommended_quantity }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No replenishment priorities.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="latest-calculation-runs">
            <h2>Latest Calculation Runs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Formula</th>
                        <th>Status</th>
                        <th>Started by</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestCalculationRuns as $run)
                        <tr>
                            <td>{{ $run->calculation_date?->toDateString() }}</td>
                            <td>{{ $run->supplier?->name }}</td>
                            <td>{{ $run->formula_version }}</td>
                            <td>{{ $run->status }}</td>
                            <td>{{ $run->startedBy?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No calculation runs.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="proposals-needing-review">
            <h2>Proposals Needing Review</h2>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Total lines</th>
                        <th>Lines needing review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposalsNeedingReview as $proposal)
                        <tr>
                            <td>{{ $proposal->supplier?->name }}</td>
                            <td>{{ $proposal->status instanceof \BackedEnum ? $proposal->status->value : $proposal->status }}</td>
                            <td>{{ $proposal->total_lines }}</td>
                            <td>{{ $proposal->lines_needing_review_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No proposals needing review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="supplier-orders-awaiting-action">
            <h2>Supplier Orders Awaiting Action</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Order date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplierOrdersAwaitingAction as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->supplier?->name }}</td>
                            <td>{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</td>
                            <td>{{ $order->order_date?->toDateString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No supplier orders awaiting action.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="emails-needing-review">
            <h2>Emails Needing Review</h2>
            <table>
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Supplier</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($emailsNeedingReview as $email)
                        <tr>
                            <td>{{ $email->from_email }}</td>
                            <td>{{ $email->subject }}</td>
                            <td>{{ $email->relatedSupplier?->name }}</td>
                            <td>{{ $email->received_at?->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No emails needing review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="form-autofill-runs-needing-review">
            <h2>Form Autofill Runs Needing Review</h2>
            <table>
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($formAutofillRunsNeedingReview as $run)
                        <tr>
                            <td>{{ $run->formTemplate?->name }}</td>
                            <td>{{ $run->emailMessage?->subject }}</td>
                            <td>{{ $run->status instanceof \BackedEnum ? $run->status->value : $run->status }}</td>
                            <td>{{ $run->confidence }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No form autofill runs needing review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section id="logistics-delays">
            <h2>Logistics Delays</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Supplier</th>
                        <th>Carrier</th>
                        <th>Delivery date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logisticsDelays as $record)
                        <tr>
                            <td>{{ $record->supplierOrder?->order_number }}</td>
                            <td>{{ $record->supplier?->name }}</td>
                            <td>{{ $record->carrier?->name }}</td>
                            <td>{{ $record->delivery_date?->toDateString() }}</td>
                            <td>{{ $record->status instanceof \BackedEnum ? $record->status->value : $record->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No logistics delays.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
