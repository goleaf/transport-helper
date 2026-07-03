<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply / Procurement Agent</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%230f766e'/%3E%3Cpath d='M4 8h8M8 4v8' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-6 border-b border-zinc-200 pb-6 dark:border-zinc-800 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase text-teal-700 dark:text-teal-300">Operations console</p>
                <h1 class="mt-3 text-3xl font-semibold leading-tight text-zinc-950 dark:text-white sm:text-5xl">
                    Supply / Procurement Agent
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-600 dark:text-zinc-300">
                    Imports, deterministic replenishment, approval queues, supplier email, AI extraction review, transport quotes and logistics in one Laravel-controlled workflow.
                </p>
            </div>

            <nav class="flex flex-col gap-3 sm:flex-row lg:justify-end" aria-label="Primary actions">
                <a
                    href="{{ route('supply.dashboard') }}"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 dark:focus:ring-offset-zinc-950"
                >
                    Open Supply Dashboard
                </a>
                <a
                    href="{{ route('supply.proposals.index') }}"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-zinc-300 px-4 text-sm font-semibold text-zinc-900 transition hover:border-zinc-400 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-zinc-500 dark:hover:bg-zinc-900 dark:focus:ring-offset-zinc-950"
                >
                    Review Proposals
                </a>
            </nav>
        </header>

        <section class="grid grid-cols-1 gap-3 md:grid-cols-3" aria-label="Project guardrails">
            <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/50">
                <h2 class="text-sm font-semibold text-teal-950 dark:text-teal-100">Deterministic replenishment</h2>
                <p class="mt-2 text-sm leading-6 text-teal-900 dark:text-teal-200">Order need is calculated by Laravel services only, with formula evidence and review flags.</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
                <h2 class="text-sm font-semibold text-amber-950 dark:text-amber-100">Human approval required</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900 dark:text-amber-200">Quantities, supplier email sending, extraction acceptance, carrier choice and mismatches stay user-controlled.</p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/40">
                <h2 class="text-sm font-semibold text-sky-950 dark:text-sky-100">AI suggestions stay separate</h2>
                <p class="mt-2 text-sm leading-6 text-sky-900 dark:text-sky-200">AI may read and suggest from email, but it cannot mutate orders, logistics, quantities or carrier selection.</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1.15fr_0.85fr]" aria-label="Workflow entry points">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase text-zinc-500 dark:text-zinc-400">Work queue</p>
                        <h2 class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">Start from the current workflow stage</h2>
                    </div>
                    <a class="text-sm font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-300 dark:hover:text-teal-200" href="{{ route('supply.dashboard') }}">
                        Full dashboard
                    </a>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.imports.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Imports</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Sales history, stock, inbound orders, reservations and product rules.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.calculations.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Calculations</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">T0/T1/T2/T3, trend, raw need, rounding and formula warnings.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.proposals.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Order proposals</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Approve, adjust with reason, reject and convert to supplier orders.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.supplier-orders.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Supplier orders</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Export files, prepare email drafts, approve and send safely.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.emails.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Inbound email</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Store supplier replies, match supplier/order context and queue analysis.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.ai-extractions.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">AI extractions</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Accept, reject or keep needs-review without applying business changes.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.transport.quotes.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Transport quotes</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Compare quote options while keeping carrier selection manual.</span>
                    </a>
                    <a class="group rounded-lg border border-zinc-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-800 dark:hover:border-teal-800 dark:hover:bg-teal-950/30" href="{{ route('supply.logistics.index') }}">
                        <span class="text-sm font-semibold text-zinc-950 dark:text-white">Logistics</span>
                        <span class="mt-2 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Track ready dates, pickup, delivery, delays, receiving and records.</span>
                    </a>
                </div>
            </div>

            <aside class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900" aria-label="Human approval points">
                <p class="text-sm font-semibold uppercase text-zinc-500 dark:text-zinc-400">Approval points</p>
                <h2 class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">Do not automate these</h2>

                <div class="mt-5 flex flex-col gap-3">
                    <a class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-950" href="{{ route('supply.proposals.index') }}">
                        <span class="block text-sm font-semibold text-zinc-950 dark:text-white">Order quantity approval</span>
                        <span class="mt-1 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Approve, adjust or reject with audit evidence.</span>
                    </a>
                    <a class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-950" href="{{ route('supply.supplier-orders.index') }}">
                        <span class="block text-sm font-semibold text-zinc-950 dark:text-white">Supplier email sending</span>
                        <span class="mt-1 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Drafts wait for explicit approval before send.</span>
                    </a>
                    <a class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-950" href="{{ route('supply.ai-extractions.index') }}">
                        <span class="block text-sm font-semibold text-zinc-950 dark:text-white">AI extraction review</span>
                        <span class="mt-1 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Accepted extraction remains separate until a later Laravel apply service.</span>
                    </a>
                    <a class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-950" href="{{ route('supply.transport.quotes.index') }}">
                        <span class="block text-sm font-semibold text-zinc-950 dark:text-white">Carrier selection</span>
                        <span class="mt-1 block text-sm leading-6 text-zinc-600 dark:text-zinc-300">Quotes can be compared, but the user chooses the carrier.</span>
                    </a>
                </div>
            </aside>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900" aria-label="Process map">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-zinc-500 dark:text-zinc-400">Process map</p>
                    <h2 class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">Laravel validates, people approve, audit records it</h2>
                </div>
                <div class="flex gap-2 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                    <span class="rounded-md border border-zinc-200 px-2.5 py-1 dark:border-zinc-700">T0</span>
                    <span class="rounded-md border border-zinc-200 px-2.5 py-1 dark:border-zinc-700">T1</span>
                    <span class="rounded-md border border-zinc-200 px-2.5 py-1 dark:border-zinc-700">T2</span>
                    <span class="rounded-md border border-zinc-200 px-2.5 py-1 dark:border-zinc-700">T3</span>
                </div>
            </div>

            <ol class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <li class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-950">
                    <span class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">01</span>
                    <h3 class="mt-2 text-sm font-semibold text-zinc-950 dark:text-white">Import clean data</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Rows keep raw and normalized values with validation errors.</p>
                </li>
                <li class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-950">
                    <span class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">02</span>
                    <h3 class="mt-2 text-sm font-semibold text-zinc-950 dark:text-white">Calculate need</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Formula output includes trend, safety stock, raw need and rounding.</p>
                </li>
                <li class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-950">
                    <span class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">03</span>
                    <h3 class="mt-2 text-sm font-semibold text-zinc-950 dark:text-white">Resolve proposals</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Only resolved, positive lines become supplier order items.</p>
                </li>
                <li class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-950">
                    <span class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">04</span>
                    <h3 class="mt-2 text-sm font-semibold text-zinc-950 dark:text-white">Control downstream work</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Email, confirmation, transport and logistics changes require review.</p>
                </li>
            </ol>
        </section>

        <footer class="flex flex-col gap-3 border-t border-zinc-200 py-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
            <span>External AI, email providers and carrier integrations stay disabled until configured and approved.</span>
            <nav class="flex flex-wrap gap-3" aria-label="Administrative links">
                <a class="font-semibold text-zinc-800 hover:text-teal-700 dark:text-zinc-200 dark:hover:text-teal-300" href="{{ route('supply.audit-logs.index') }}">Audit Logs</a>
                <a class="font-semibold text-zinc-800 hover:text-teal-700 dark:text-zinc-200 dark:hover:text-teal-300" href="{{ route('supply.settings.index') }}">Settings</a>
                <a class="font-semibold text-zinc-800 hover:text-teal-700 dark:text-zinc-200 dark:hover:text-teal-300" href="{{ route('supply.integrations.index') }}">Integrations</a>
            </nav>
        </footer>
    </main>
</body>
</html>
