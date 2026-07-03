<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ManualInboundEmailRequest;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Services\Email\EmailIngestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ManualInboundEmailController extends Controller
{
    public function create(): View
    {
        Gate::authorize('createManual', EmailMessage::class);

        return view('supply.emails.create-manual', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'emailAccounts' => EmailAccount::query()->select(['id', 'company_id', 'name', 'email_address'])->orderBy('name')->get(),
        ]);
    }

    public function store(ManualInboundEmailRequest $request, EmailIngestionService $emailIngestionService): RedirectResponse
    {
        $validated = $request->validated();
        $company = Company::query()->findOrFail($validated['company_id']);
        $account = isset($validated['email_account_id'])
            ? EmailAccount::query()->where('company_id', $company->id)->findOrFail($validated['email_account_id'])
            : null;

        $message = [
            'message_id' => $validated['message_id'] ?? null,
            'thread_id' => $validated['thread_id'] ?? null,
            'from_email' => $validated['from_email'],
            'to' => $validated['to'] ?? [],
            'cc' => $validated['cc'] ?? [],
            'subject' => $validated['subject'] ?? null,
            'body_text' => $validated['body_text'] ?? null,
            'body_html' => $validated['body_html'] ?? null,
            'received_at' => $validated['received_at'] ?? now()->toDateTimeString(),
            'attachments' => $this->uploadedAttachments($request),
        ];

        $result = $emailIngestionService->ingest($company, $account, 'manual', [
            'messages' => [$message],
            'analyze' => (bool) ($validated['analyze'] ?? false),
            'sync_analysis' => (bool) ($validated['sync_analysis'] ?? false),
            'analyzer' => $validated['analyzer'] ?? 'rule_based',
        ], $request->user());

        $email = collect($result['messages'] ?? [])->first();

        return redirect()
            ->route('supply.emails.show', $email)
            ->with('status', 'Inbound email stored.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function uploadedAttachments(ManualInboundEmailRequest $request): array
    {
        return collect($request->file('attachments', []))
            ->map(fn ($file): array => [
                'original_filename' => $file->getClientOriginalName(),
                'content' => $file->get(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ])
            ->values()
            ->all();
    }
}
