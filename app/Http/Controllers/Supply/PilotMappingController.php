<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SavePilotCarrierMappingRequest;
use App\Http\Requests\Supply\SavePilotEmailMappingRequest;
use App\Http\Requests\Supply\SavePilotImportMappingRequest;
use App\Http\Requests\Supply\SavePilotLogisticsMappingRequest;
use App\Http\Requests\Supply\SavePilotManufacturerFormMappingRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotMappingService;
use Illuminate\Http\RedirectResponse;

class PilotMappingController extends Controller
{
    public function saveImport(SavePilotImportMappingRequest $request, PilotSupplier $pilot, PilotMappingService $service): RedirectResponse
    {
        $service->saveImportMapping($pilot, (string) $request->validated('import_type'), $request->validated('mapping'), $request->user());

        return back()->with('status', 'Pilot import mapping saved.');
    }

    public function saveManufacturerForm(SavePilotManufacturerFormMappingRequest $request, PilotSupplier $pilot, PilotMappingService $service): RedirectResponse
    {
        $service->saveManufacturerFormMapping($pilot, $request->validated('mapping'), $request->user());

        return back()->with('status', 'Pilot manufacturer form mapping saved.');
    }

    public function saveEmail(SavePilotEmailMappingRequest $request, PilotSupplier $pilot, PilotMappingService $service): RedirectResponse
    {
        $service->saveEmailSampleMapping($pilot, (string) $request->validated('sample_type'), $request->validated('mapping'), $request->user());

        return back()->with('status', 'Pilot email sample mapping saved.');
    }

    public function saveCarrier(SavePilotCarrierMappingRequest $request, PilotSupplier $pilot, PilotMappingService $service): RedirectResponse
    {
        $service->saveCarrierMapping($pilot, $request->validated('mapping'), $request->user());

        return back()->with('status', 'Pilot carrier quote mapping saved.');
    }

    public function saveLogistics(SavePilotLogisticsMappingRequest $request, PilotSupplier $pilot, PilotMappingService $service): RedirectResponse
    {
        $service->saveLogisticsMapping($pilot, $request->validated('mapping'), $request->user());

        return back()->with('status', 'Pilot logistics mapping saved.');
    }
}
