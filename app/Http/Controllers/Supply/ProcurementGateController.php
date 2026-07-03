<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RunProcurementGateRequest;
use App\Services\Supply\Procurement\ProcurementGateService;
use App\Services\Supply\Procurement\ProcurementSubjectResolver;
use Illuminate\Contracts\View\View;

class ProcurementGateController extends Controller
{
    public function store(RunProcurementGateRequest $request, ProcurementSubjectResolver $resolver, ProcurementGateService $gateService): View
    {
        $validated = $request->validated();
        $subject = $resolver->resolve($validated['type'], (int) $validated['id']);
        $result = $gateService->gate($subject, $validated['action'], $request->user());

        return view('supply.procurement.reports.gate-result', [
            'result' => $result,
            'subject' => $subject,
            'subjectType' => $validated['type'],
        ]);
    }
}
