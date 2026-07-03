<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AnalyzeInboundEmailRequest;
use App\Jobs\AnalyzeInboundEmailJob;
use App\Models\EmailMessage;
use App\Services\AI\Email\AiEmailAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnalyzeInboundEmailController extends Controller
{
    public function store(AnalyzeInboundEmailRequest $request, EmailMessage $email, AiEmailAnalysisService $analysisService): RedirectResponse
    {
        Gate::authorize('analyze', $email);

        $validated = $request->validated();
        $options = [
            'analyzer' => $validated['analyzer'] ?? 'rule_based',
            'force' => (bool) ($validated['force'] ?? false),
            'fake_output' => $validated['fake_output'] ?? null,
        ];

        if (($validated['sync'] ?? true) === false) {
            AnalyzeInboundEmailJob::dispatch($email->id, $options);
            $email->forceFill(['status' => 'analysis_pending'])->save();

            return redirect()->route('supply.emails.show', $email)->with('status', 'Email analysis queued.');
        }

        $result = $analysisService->analyze($email, $options, $request->user());

        return redirect()
            ->route('supply.ai-extractions.show', $result['extraction'])
            ->with('status', 'Email analyzed.');
    }
}
