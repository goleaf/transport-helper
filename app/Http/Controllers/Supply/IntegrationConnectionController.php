<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreIntegrationConnectionRequest;
use App\Http\Requests\Supply\UpdateIntegrationConnectionRequest;
use App\Models\Company;
use App\Models\IntegrationConnection;
use App\Services\Supply\Integrations\IntegrationConfigService;
use App\Services\Supply\Integrations\IntegrationCredentialService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IntegrationConnectionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', IntegrationConnection::class);

        return view('supply.integrations.index', [
            'connections' => IntegrationConnection::query()
                ->select(['id', 'company_id', 'type', 'provider', 'name', 'environment', 'status', 'approval_status', 'last_test_status', 'last_tested_at', 'is_external', 'is_active'])
                ->with('company:id,name')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', IntegrationConnection::class);

        return view('supply.integrations.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'configText' => json_encode(['configured' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'formValues' => [
                'company_id' => null,
                'name' => null,
                'provider' => 'manual',
                'type' => 'manual',
                'environment' => 'test',
                'is_external' => true,
                'requires_approval' => true,
                'notes' => null,
            ],
        ]);
    }

    public function store(StoreIntegrationConnectionRequest $request, IntegrationConfigService $service): RedirectResponse
    {
        $result = $service->createConnection($request->validated(), $request->user());

        return redirect()
            ->route('supply.integrations.show', $result['connection'])
            ->with('status', 'Integration connection configured. Approval is required before activation.');
    }

    public function show(IntegrationConnection $connection, IntegrationCredentialService $credentials): View
    {
        Gate::authorize('view', $connection);

        $connection->load(['company:id,name', 'approvedBy:id,name']);
        $maskedConfig = $credentials->maskConfig($connection->encrypted_config ?? []);

        return view('supply.integrations.show', [
            'connection' => $connection,
            'maskedConfigLines' => $this->flattenConfig($maskedConfig),
        ]);
    }

    public function edit(IntegrationConnection $connection, IntegrationCredentialService $credentials): View
    {
        Gate::authorize('update', $connection);
        $maskedConfig = $credentials->maskConfig($connection->encrypted_config ?? []);

        return view('supply.integrations.edit', [
            'connection' => $connection,
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'maskedConfig' => $maskedConfig,
            'configText' => json_encode($maskedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'formValues' => [
                'company_id' => $connection->company_id,
                'name' => $connection->name,
                'provider' => $connection->provider,
                'type' => $connection->type instanceof \BackedEnum ? $connection->type->value : $connection->type,
                'environment' => $connection->environment,
                'is_external' => $connection->is_external,
                'requires_approval' => $connection->requires_approval,
                'notes' => $connection->notes,
            ],
        ]);
    }

    public function update(UpdateIntegrationConnectionRequest $request, IntegrationConnection $connection, IntegrationConfigService $service): RedirectResponse
    {
        $service->updateConnection($connection, $request->validated(), $request->user());

        return redirect()
            ->route('supply.integrations.show', $connection)
            ->with('status', 'Integration connection updated.');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{key:string,value:string}>
     */
    private function flattenConfig(array $config, string $prefix = ''): array
    {
        $lines = [];

        foreach ($config as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                array_push($lines, ...$this->flattenConfig($value, $path));

                continue;
            }

            $lines[] = [
                'key' => $path,
                'value' => is_scalar($value) ? (string) $value : '[value]',
            ];
        }

        return $lines;
    }
}
