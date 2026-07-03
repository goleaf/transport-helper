<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmailMessageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EmailMessage::class);

        $emails = EmailMessage::query()
            ->select([
                'id',
                'company_id',
                'email_account_id',
                'direction',
                'message_id',
                'thread_id',
                'from_email',
                'subject',
                'received_at',
                'sent_at',
                'related_supplier_id',
                'related_supplier_order_id',
                'status',
                'created_at',
            ])
            ->with([
                'emailAccount:id,name,email_address',
                'relatedSupplier:id,name',
                'relatedSupplierOrder:id,order_number',
            ])
            ->withCount(['attachments', 'aiEmailExtractions'])
            ->when($request->filled('direction'), fn ($query) => $query->where('direction', $request->string('direction')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('related_supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('supplier_order_id'), fn ($query) => $query->where('related_supplier_order_id', $request->integer('supplier_order_id')))
            ->when($request->filled('from_email'), fn ($query) => $query->where('from_email', $request->string('from_email')->toString()))
            ->when($request->boolean('needs_review'), fn ($query) => $query->where(function ($query): void {
                $query->where('status', 'needs_review')
                    ->orWhereHas('aiEmailExtractions', fn ($query) => $query->where('requires_human_review', true));
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.emails.index', [
            'emails' => $emails,
        ]);
    }

    public function show(EmailMessage $email): View
    {
        Gate::authorize('view', $email);

        $email->load([
            'emailAccount:id,name,email_address',
            'relatedSupplier:id,name',
            'relatedSupplierOrder:id,order_number',
            'attachments:id,email_message_id,original_filename,stored_path,mime_type,size_bytes,checksum',
            'aiEmailExtractions:id,email_message_id,provider,prompt_version,output_json,confidence,requires_human_review,review_reason,accepted_at,rejected_at,created_at',
            'formAutofillRuns:id,email_message_id,form_template_id,status,confidence,created_at',
            'formAutofillRuns.formTemplate:id,name,context_type',
        ]);

        return view('supply.emails.show', [
            'email' => $email,
            'canAnalyze' => Gate::allows('analyze', $email),
        ]);
    }
}
